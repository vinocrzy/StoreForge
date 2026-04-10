// Tool: extract_page
// Extract title, description, images, and layout info from any URL

import { type McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { z } from 'zod';
import { createPage } from '../browser.js';
import { searchCache } from '../cache.js';
import type { ImageResult, PageExtract } from '../types.js';

const MIN_WIDTH = Number(process.env.MIN_IMAGE_WIDTH ?? 800);

// ─── Validation ───────────────────────────────────────────────────────────────

function sanitizeUrl(raw: string): string {
  const url = new URL(raw); // throws on invalid URLs
  if (!['https:', 'http:'].includes(url.protocol)) {
    throw new Error('Only http/https URLs are supported');
  }
  // Block private/local addresses (SSRF prevention)
  const hostname = url.hostname.toLowerCase();
  const blocked = ['localhost', '127.0.0.1', '0.0.0.0', '::1'];
  if (
    blocked.includes(hostname) ||
    hostname.startsWith('192.168.') ||
    hostname.startsWith('10.') ||
    hostname.startsWith('172.')
  ) {
    throw new Error('Private/local addresses are not allowed');
  }
  return url.toString();
}

// ─── Main extract function ────────────────────────────────────────────────────

export async function extractPage(url: string, minWidth = MIN_WIDTH): Promise<PageExtract> {
  const safeUrl = sanitizeUrl(url);

  const cacheKey = `extract:${safeUrl}:${minWidth}`;
  const cached = searchCache.get(cacheKey) as PageExtract | undefined;
  if (cached) return cached;

  const page = await createPage();
  try {
    await page.goto(safeUrl, { waitUntil: 'domcontentloaded', timeout: 25_000 });
    await page.evaluate(() => window.scrollBy(0, 400));
    await page.waitForTimeout(800);

    const extract = await page.evaluate(
      ({ minW }: { minW: number }) => {
        const getMeta = (name: string): string =>
          (
            document.querySelector(`meta[name="${name}"]`) as HTMLMetaElement | null
          )?.content ||
          (
            document.querySelector(`meta[property="${name}"]`) as HTMLMetaElement | null
          )?.content ||
          '';

        const images: ImageResult[] = [];
        const seen = new Set<string>();

        document.querySelectorAll<HTMLImageElement>('img').forEach(img => {
          const src = img.currentSrc || img.src || '';
          if (!src || src.startsWith('data:') || seen.has(src)) return;

          const w = img.naturalWidth || img.width || 0;
          const h = img.naturalHeight || img.height || 0;
          if (w > 0 && w < minW) return;

          seen.add(src);
          images.push({
            url: src,
            thumbnailUrl: src,
            alt: img.alt || img.title || '',
            width: w,
            height: h,
            source: window.location.hostname,
          });
        });

        const headings = Array.from(document.querySelectorAll('h1, h2'))
          .map(h => (h as HTMLElement).innerText?.trim())
          .filter(Boolean)
          .slice(0, 8);

        // Detect broad layout sections by landmark + semantic elements
        const layoutSections: string[] = [];
        const landsmarkMap: Record<string, string> = {
          header: 'header',
          nav: 'navigation',
          main: 'main-content',
          aside: 'sidebar',
          footer: 'footer',
        };
        Object.entries(landsmarkMap).forEach(([tag, label]) => {
          if (document.querySelector(tag)) layoutSections.push(label);
        });
        document.querySelectorAll('[class*="hero"],[id*="hero"]').forEach(() => {
          if (!layoutSections.includes('hero')) layoutSections.push('hero');
        });
        document.querySelectorAll('[class*="carousel"],[class*="slider"]').forEach(() => {
          if (!layoutSections.includes('carousel')) layoutSections.push('carousel');
        });

        return {
          url: window.location.href,
          title: document.title || '',
          description:
            getMeta('description') || getMeta('og:description') || '',
          ogImage: getMeta('og:image') || '',
          images,
          headings,
          layoutSections,
        } satisfies Omit<PageExtract, 'images'> & { images: ImageResult[] };
      },
      { minW: minWidth },
    );

    searchCache.set(cacheKey, extract);
    return extract as PageExtract;
  } finally {
    await page.context().close();
  }
}

// ─── MCP tool registration ────────────────────────────────────────────────────

export function registerExtractTool(server: McpServer): void {
  server.tool(
    'extract_page',
    'Extract structured content from any web page: title, description, images with dimensions, headings, and layout sections. Useful for analysing reference designs.',
    {
      url: z.string().url().describe(
        'The page URL to extract content from (https only)',
      ),
      min_width: z
        .number()
        .int()
        .min(0)
        .optional()
        .default(0)
        .describe('Minimum image width to include (0 = all images)'),
    },
    async ({ url, min_width }) => {
      try {
        const result = await extractPage(url, min_width);
        return {
          content: [{ type: 'text', text: JSON.stringify(result, null, 2) }],
        };
      } catch (err) {
        const message = err instanceof Error ? err.message : String(err);
        return {
          isError: true,
          content: [{ type: 'text', text: JSON.stringify({ error: message, url }) }],
        };
      }
    },
  );
}
