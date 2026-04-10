// Tool: search_images
// Search for design inspiration images across curated sources

import { type McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { z } from 'zod';
import { createPage } from '../browser.js';
import { searchCache } from '../cache.js';
import type { ImageResult, SearchOptions } from '../types.js';

const MIN_WIDTH = Number(process.env.MIN_IMAGE_WIDTH ?? 800);
const MAX_RESULTS = Math.min(Number(process.env.MAX_RESULTS ?? 20), 50);

// ─── Source implementations ──────────────────────────────────────────────────

async function searchUnsplash(
  query: string,
  opts: Required<SearchOptions>,
): Promise<ImageResult[]> {
  const page = await createPage();
  try {
    const slug = query.toLowerCase().replace(/\s+/g, '-');
    await page.goto(`https://unsplash.com/s/photos/${encodeURIComponent(slug)}`, {
      waitUntil: 'domcontentloaded',
      timeout: 25_000,
    });
    // Trigger lazy-load
    await page.evaluate(() => window.scrollBy(0, 800));
    await page.waitForSelector('img[src*="images.unsplash.com"]', { timeout: 12_000 })
      .catch(() => null);

    return await page.evaluate(
      ({ limit, minWidth, minHeight }: { limit: number; minWidth: number; minHeight: number }) => {
        const seen = new Set<string>();
        const results: ImageResult[] = [];

        document
          .querySelectorAll<HTMLImageElement>('img[src*="images.unsplash.com"]')
          .forEach(img => {
            if (results.length >= limit) return;

            const rawSrc = img.src || '';
            if (!rawSrc || seen.has(rawSrc)) return;

            // Skip avatars / profile pictures
            if (rawSrc.includes('/profile-') || rawSrc.includes('avatar')) return;

            const base = rawSrc.split('?')[0];
            const url = `${base}?w=1920&q=85&fm=jpg&fit=crop`;
            const thumb = `${base}?w=480&q=70&fm=jpg&fit=crop`;

            const w = img.naturalWidth || 1920;
            const h = img.naturalHeight || 1080;
            if (w < minWidth || h < minHeight) return;

            seen.add(rawSrc);
            results.push({
              url,
              thumbnailUrl: thumb,
              alt: img.alt || img.title || query,
              width: w,
              height: h,
              source: 'unsplash',
            });
          });

        return results;
      },
      { limit: opts.limit, minWidth: opts.minWidth, minHeight: opts.minHeight },
    );
  } finally {
    await page.context().close();
  }
}

async function searchPexels(
  query: string,
  opts: Required<SearchOptions>,
): Promise<ImageResult[]> {
  const page = await createPage();
  try {
    const slug = encodeURIComponent(query);
    await page.goto(`https://www.pexels.com/search/${slug}/`, {
      waitUntil: 'domcontentloaded',
      timeout: 25_000,
    });
    await page.evaluate(() => window.scrollBy(0, 600));
    await page.waitForSelector('article img', { timeout: 12_000 }).catch(() => null);

    return await page.evaluate(
      ({ limit, minWidth, minHeight }: { limit: number; minWidth: number; minHeight: number }) => {
        const seen = new Set<string>();
        const results: ImageResult[] = [];

        document.querySelectorAll<HTMLImageElement>('article img').forEach(img => {
          if (results.length >= limit) return;

          // Pexels uses srcset — pick the highest resolution entry
          const srcset = img.srcset || '';
          const sources = srcset
            .split(',')
            .map(s => s.trim().split(/\s+/))
            .filter(p => p[0]);
          const best = sources.at(-1)?.[0] || img.src;
          if (!best || seen.has(best)) return;

          const w = img.naturalWidth || 0;
          const h = img.naturalHeight || 0;
          if (w > 0 && (w < minWidth || h < minHeight)) return;

          seen.add(best);
          results.push({
            url: best,
            thumbnailUrl: img.src || best,
            alt: img.alt || img.title || '',
            width: w || 1920,
            height: h || 1080,
            source: 'pexels',
          });
        });

        return results;
      },
      { limit: opts.limit, minWidth: opts.minWidth, minHeight: opts.minHeight },
    );
  } finally {
    await page.context().close();
  }
}

// ─── Main search function ─────────────────────────────────────────────────────

export async function searchImages(
  query: string,
  options: SearchOptions = {},
): Promise<ImageResult[]> {
  const opts: Required<SearchOptions> = {
    limit: options.limit ?? MAX_RESULTS,
    minWidth: options.minWidth ?? MIN_WIDTH,
    minHeight: options.minHeight ?? 0,
    allowedDomains: options.allowedDomains ?? [],
    source: options.source ?? 'auto',
  };

  const cacheKey = `search:${query}:${JSON.stringify(opts)}`;
  const cached = searchCache.get(cacheKey) as ImageResult[] | undefined;
  if (cached) return cached;

  let results: ImageResult[] = [];

  if (opts.source === 'unsplash' || opts.source === 'auto') {
    results = await searchUnsplash(query, opts).catch(() => []);
  }

  if ((opts.source === 'pexels' || opts.source === 'auto') && results.length < opts.limit / 2) {
    const pexels = await searchPexels(query, opts).catch(() => []);
    // Merge — deduplicate by URL
    const seen = new Set(results.map(r => r.url));
    results.push(...pexels.filter(r => !seen.has(r.url)));
  }

  // Apply domain filter if provided
  if (opts.allowedDomains.length > 0) {
    results = results.filter(r => {
      try {
        const host = new URL(r.url).hostname;
        return opts.allowedDomains.some(d => host.endsWith(d));
      } catch {
        return false;
      }
    });
  }

  const trimmed = results.slice(0, opts.limit);
  searchCache.set(cacheKey, trimmed);
  return trimmed;
}

// ─── MCP tool registration ────────────────────────────────────────────────────

export function registerSearchTool(server: McpServer): void {
  server.tool(
    'search_images',
    'Search for design inspiration images across Unsplash and Pexels. Returns structured image data (URL, dimensions, alt text).',
    {
      query: z.string().min(1).max(200).describe(
        'Search query — e.g. "e-commerce hero section", "artisan soap product page", "minimalist checkout"',
      ),
      limit: z.number().int().min(1).max(50).optional().default(12).describe(
        'Maximum number of images to return (default 12, max 50)',
      ),
      min_width: z.number().int().min(0).optional().default(1024).describe(
        'Minimum image width in pixels (default 1024)',
      ),
      source: z
        .enum(['unsplash', 'pexels', 'auto'])
        .optional()
        .default('auto')
        .describe('Image source to search (default: auto tries Unsplash first, then Pexels)'),
      allowed_domains: z
        .array(z.string())
        .optional()
        .default([])
        .describe('If provided, only return images from these domains'),
    },
    async ({ query, limit, min_width, source, allowed_domains }) => {
      try {
        const results = await searchImages(query, {
          limit,
          minWidth: min_width,
          source,
          allowedDomains: allowed_domains,
        });

        return {
          content: [
            {
              type: 'text',
              text: JSON.stringify(
                {
                  query,
                  total: results.length,
                  images: results,
                },
                null,
                2,
              ),
            },
          ],
        };
      } catch (err) {
        const message = err instanceof Error ? err.message : String(err);
        return {
          isError: true,
          content: [{ type: 'text', text: JSON.stringify({ error: message }) }],
        };
      }
    },
  );
}
