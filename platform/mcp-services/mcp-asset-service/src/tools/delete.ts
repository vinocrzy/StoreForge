// delete_asset tool — removes a stored asset and its manifest entry

import { unlink } from 'node:fs/promises';
import { join } from 'node:path';
import { z } from 'zod';
import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { getAssetsDir, getAssetById, removeAsset } from '../manifest.js';

const DeleteAssetSchema = z.object({
  asset_id: z.string().uuid().describe('UUID of the asset to delete'),
});

export function registerDeleteTool(server: McpServer): void {
  server.tool(
    'delete_asset',
    'Permanently delete a stored asset file and remove it from the manifest.',
    DeleteAssetSchema.shape,
    async (input) => {
      const { asset_id } = DeleteAssetSchema.parse(input);

      const asset = await getAssetById(asset_id);
      if (!asset) {
        return {
          content: [
            {
              type: 'text' as const,
              text: JSON.stringify({ error: `Asset ${asset_id} not found` }),
            },
          ],
          isError: true,
        };
      }

      const assetsDir = getAssetsDir();
      const absPath = join(assetsDir, asset.localPath);

      try {
        await unlink(absPath);
      } catch (err: unknown) {
        // File already missing — continue to clean up the manifest entry
        const code = (err as NodeJS.ErrnoException).code;
        if (code !== 'ENOENT') {
          const message = err instanceof Error ? err.message : 'Delete failed';
          return {
            content: [
              { type: 'text' as const, text: JSON.stringify({ error: message }) },
            ],
            isError: true,
          };
        }
      }

      await removeAsset(asset_id);

      return {
        content: [
          {
            type: 'text' as const,
            text: JSON.stringify({
              success: true,
              deleted: { id: asset.id, filename: asset.filename, category: asset.category },
            }),
          },
        ],
      };
    }
  );
}
