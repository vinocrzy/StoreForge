// optimize_asset tool — re-optimize a stored asset using sharp

import { join } from 'node:path';
import { z } from 'zod';
import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { getAssetsDir, getAssetById, updateAsset } from '../manifest.js';
import { optimizeFile } from '../optimizer.js';

const OptimizeAssetSchema = z.object({
  asset_id: z.string().uuid().describe('UUID of the asset to optimize'),
  quality: z
    .number()
    .int()
    .min(1)
    .max(100)
    .optional()
    .describe('WebP quality 1-100 (default: OPTIMIZE_QUALITY env or 85)'),
  max_width: z
    .number()
    .int()
    .min(1)
    .optional()
    .describe('Maximum output width in pixels'),
  max_height: z
    .number()
    .int()
    .min(1)
    .optional()
    .describe('Maximum output height in pixels'),
});

export function registerOptimizeTool(server: McpServer): void {
  server.tool(
    'optimize_asset',
    'Re-optimize an already-stored asset (resize, compress, convert to WebP). Updates the manifest record in place.',
    OptimizeAssetSchema.shape,
    async (input) => {
      const { asset_id, quality, max_width, max_height } = OptimizeAssetSchema.parse(input);

      const asset = await getAssetById(asset_id);
      if (!asset) {
        return {
          content: [{ type: 'text' as const, text: JSON.stringify({ error: `Asset ${asset_id} not found` }) }],
          isError: true,
        };
      }

      const assetsDir = getAssetsDir();
      const absPath = join(assetsDir, asset.localPath);

      try {
        const result = await optimizeFile(absPath, {
          quality,
          maxWidth: max_width,
          maxHeight: max_height,
        });

        await updateAsset(asset_id, {
          width: result.width,
          height: result.height,
          fileSize: result.fileSize,
          format: 'webp',
          optimized: true,
          updatedAt: new Date().toISOString(),
        });

        const updated = await getAssetById(asset_id);
        return {
          content: [
            {
              type: 'text' as const,
              text: JSON.stringify({ success: true, asset: updated }, null, 2),
            },
          ],
        };
      } catch (err: unknown) {
        const message = err instanceof Error ? err.message : 'Optimization failed';
        return {
          content: [{ type: 'text' as const, text: JSON.stringify({ error: message }) }],
          isError: true,
        };
      }
    }
  );
}
