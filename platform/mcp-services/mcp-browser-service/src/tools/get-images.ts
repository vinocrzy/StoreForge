// Tool: get_images
// Convenience tool: search + strict quality filtering + deduplication
// Returns only the image URLs array — optimised for UI usage

import { type McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { z } from 'zod';
import { searchImages } from './search.js';

// ─── MCP tool registration ────────────────────────────────────────────────────

export function registerGetImagesTool(server: McpServer): void {
  server.tool(
    'get_images',
    'Search and return high-quality image URLs ready for download. Applies strict resolution filtering and deduplication. Returns a flat URL list alongside metadata.',
    {
      query: z.string().min(1).max(200).describe(
        'Design topic — e.g. "e-commerce product card dark theme", "artisan soap natural ingredients"',
      ),
      limit: z.number().int().min(1).max(30).optional().default(8).describe(
        'Maximum number of images (default 8)',
      ),
      min_width: z
        .number()
        .int()
        .min(0)
        .optional()
        .default(1280)
        .describe('Minimum image width in pixels (default 1280)'),
      source: z
        .enum(['unsplash', 'pexels', 'auto'])
        .optional()
        .default('auto')
        .describe('Image source'),
    },
    async ({ query, limit, min_width, source }) => {
      try {
        const results = await searchImages(query, {
          limit,
          minWidth: min_width,
          source,
        });

        // Deduplicate by base URL (strip query params for comparison)
        const seen = new Set<string>();
        const unique = results.filter(r => {
          const base = r.url.split('?')[0];
          if (seen.has(base)) return false;
          seen.add(base);
          return true;
        });

        const urls = unique.map(r => r.url);

        return {
          content: [
            {
              type: 'text',
              text: JSON.stringify(
                {
                  query,
                  total: unique.length,
                  urls,
                  images: unique,
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
