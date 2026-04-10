// Image optimizer — uses sharp to resize and compress assets to WebP

import sharp from 'sharp';
import type { OptimizeOptions } from './types.js';

const DEFAULT_QUALITY = Number(process.env.OPTIMIZE_QUALITY ?? 85);
const DEFAULT_MAX_WIDTH = Number(process.env.OPTIMIZE_MAX_WIDTH ?? 2400);

export interface OptimizeResult {
  buffer: Buffer;
  width: number;
  height: number;
  format: 'webp';
  fileSize: number;
}

/**
 * Optimize an image buffer using sharp.
 * Always converts to WebP. Resizes if exceeds maxWidth.
 */
export async function optimizeBuffer(
  input: Buffer | string,
  options: OptimizeOptions = {}
): Promise<OptimizeResult> {
  const quality = options.quality ?? DEFAULT_QUALITY;
  const maxWidth = options.maxWidth ?? DEFAULT_MAX_WIDTH;

  let pipeline = sharp(input as Buffer);

  // Resize if wider than maxWidth (preserve aspect ratio)
  const meta = await pipeline.metadata();
  if (meta.width && meta.width > maxWidth) {
    pipeline = pipeline.resize({ width: maxWidth, withoutEnlargement: true });
  } else if (options.maxHeight && meta.height && meta.height > options.maxHeight) {
    pipeline = pipeline.resize({ height: options.maxHeight, withoutEnlargement: true });
  }

  const buffer = await pipeline
    .webp({ quality, effort: 4 })
    .toBuffer({ resolveWithObject: false });

  // Re-read metadata from the output buffer
  const outMeta = await sharp(buffer).metadata();

  return {
    buffer,
    width: outMeta.width ?? 0,
    height: outMeta.height ?? 0,
    format: 'webp',
    fileSize: buffer.byteLength,
  };
}

/**
 * Optimize an image file and write it back to the same path.
 */
export async function optimizeFile(
  filePath: string,
  options: OptimizeOptions = {}
): Promise<OptimizeResult> {
  const result = await optimizeBuffer(filePath, options);
  await sharp(result.buffer).toFile(filePath);
  return result;
}
