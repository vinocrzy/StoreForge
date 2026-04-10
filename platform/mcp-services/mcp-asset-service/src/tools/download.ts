// download_assets tool — fetches remote images and stores them locally

import { writeFile, mkdir } from 'node:fs/promises';
import { join, extname } from 'node:path';
import { randomUUID } from 'node:crypto';
import { z } from 'zod';
import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { getAssetsDir, addAsset } from '../manifest.js';
import { validateImageUrl, validateContentType, validateFileSize } from '../validator.js';
import { optimizeBuffer } from '../optimizer.js';
import type { AssetRecord } from '../types.js';

const DownloadItemSchema = z.object({
  url: z.string().url(),
  altText: z.string().optional(),
  tags: z.array(z.string()).optional(),
});

const DownloadAssetsSchema = z.object({
  assets: z
    .array(DownloadItemSchema)
    .min(1)
    .max(20)
    .describe('List of images to download (max 20)'),
  category: z
    .string()
    .default('general')
    .describe('Storage category: hero, products, categories, general…'),
  optimize: z
    .boolean()
    .default(true)
    .describe('Convert to WebP and compress on download'),
});

type DownloadAssetsInput = z.infer<typeof DownloadAssetsSchema>;

async function downloadAssets(input: DownloadAssetsInput) {
  const { assets, category, optimize } = input;
  const assetsDir = getAssetsDir();
  const categoryDir = join(assetsDir, 'images', category);
  await mkdir(categoryDir, { recursive: true });

  const results: Array<AssetRecord | { url: string; error: string }> = [];

  for (const item of assets) {
    const urlValidation = validateImageUrl(item.url);
    if (!urlValidation.valid) {
      results.push({ url: item.url, error: urlValidation.error ?? 'Invalid URL' });
      continue;
    }

    try {
      const response = await fetch(item.url, {
        headers: { 'User-Agent': 'StoreForge-AssetService/1.0' },
        signal: AbortSignal.timeout(30_000),
        redirect: 'follow',
      });

      if (!response.ok) {
        results.push({ url: item.url, error: `HTTP ${response.status}` });
        continue;
      }

      const contentTypeHeader = response.headers.get('content-type');
      const ctValidation = validateContentType(contentTypeHeader);
      if (!ctValidation.valid) {
        results.push({ url: item.url, error: ctValidation.error ?? 'Bad content-type' });
        continue;
      }

      const bytes = await response.arrayBuffer();
      const rawBuffer = Buffer.from(bytes);

      const sizeValidation = validateFileSize(rawBuffer.byteLength);
      if (!sizeValidation.valid) {
        results.push({ url: item.url, error: sizeValidation.error ?? 'File too large' });
        continue;
      }

      const id = randomUUID();
      const filename = `${id}.webp`;
      const localRelPath = join('images', category, filename);
      const localAbsPath = join(assetsDir, localRelPath);

      let width = 0;
      let height = 0;
      let fileSize = rawBuffer.byteLength;
      let finalBuffer = rawBuffer;

      if (optimize) {
        const optimized = await optimizeBuffer(rawBuffer);
        width = optimized.width;
        height = optimized.height;
        fileSize = optimized.fileSize;
        finalBuffer = optimized.buffer;
      } else {
        // Derive dimensions from the raw buffer via sharp
        const { default: sharp } = await import('sharp');
        const meta = await sharp(rawBuffer).metadata();
        width = meta.width ?? 0;
        height = meta.height ?? 0;
      }

      await writeFile(localAbsPath, finalBuffer);

      // Auto-generate alt text from URL basename if not provided
      const basename = new URL(item.url).pathname
        .split('/')
        .pop()
        ?.replace(/[-_]/g, ' ')
        .replace(/\.[^.]+$/, '') ?? '';
      const altText = item.altText ?? basename;

      const record: AssetRecord = {
        id,
        filename,
        category,
        originalUrl: item.url,
        localPath: localRelPath,
        fileSize,
        width,
        height,
        format: 'webp',
        altText,
        tags: item.tags ?? [],
        optimized: optimize,
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString(),
      };

      await addAsset(record);
      results.push(record);
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Unknown error';
      results.push({ url: item.url, error: message });
    }
  }

  return results;
}

export function registerDownloadTool(server: McpServer): void {
  server.tool(
    'download_assets',
    'Download remote images and store them locally with optional WebP optimization. Returns an array of stored asset records or error messages for failed items.',
    DownloadAssetsSchema.shape,
    async (input) => {
      const parsed = DownloadAssetsSchema.parse(input);
      const results = await downloadAssets(parsed);
      return {
        content: [
          {
            type: 'text' as const,
            text: JSON.stringify({ downloaded: results.length, results }, null, 2),
          },
        ],
      };
    }
  );
}
