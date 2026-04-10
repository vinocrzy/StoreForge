// get_assets tool — lists stored assets, optionally filtered by category

import { z } from 'zod';
import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { getAssetsByCategory, readManifest } from '../manifest.js';

const GetAssetsSchema = z.object({
  category: z
    .string()
    .optional()
    .describe('Filter by category (hero, products, categories…). Omit to return all.'),
  tag: z.string().optional().describe('Filter results to assets that include this tag'),
  limit: z.number().int().min(1).max(500).default(100).describe('Maximum results to return'),
  offset: z.number().int().min(0).default(0).describe('Pagination offset'),
});

export function registerGetAssetsTool(server: McpServer): void {
  server.tool(
    'get_assets',
    'List stored assets from the local manifest. Filter by category or tag and paginate results.',
    GetAssetsSchema.shape,
    async (input) => {
      const { category, tag, limit, offset } = GetAssetsSchema.parse(input);

      const allAssets = await getAssetsByCategory(category);

      const filtered = tag
        ? allAssets.filter((a) => a.tags.includes(tag))
        : allAssets;

      const total = filtered.length;
      const page = filtered.slice(offset, offset + limit);

      return {
        content: [
          {
            type: 'text' as const,
            text: JSON.stringify({ total, offset, limit, assets: page }, null, 2),
          },
        ],
      };
    }
  );
}

export function registerGetManifestTool(server: McpServer): void {
  server.tool(
    'get_manifest',
    'Return the full asset manifest including metadata and statistics.',
    {},
    async () => {
      const manifest = await readManifest();
      return {
        content: [
          {
            type: 'text' as const,
            text: JSON.stringify(manifest, null, 2),
          },
        ],
      };
    }
  );
}
