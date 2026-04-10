// Image validation — checks format, size, and dimensions before storing

import type { ImageFormat } from './types.js';

const ALLOWED_CONTENT_TYPES: Record<string, ImageFormat> = {
  'image/jpeg': 'jpg',
  'image/jpg': 'jpg',
  'image/png': 'png',
  'image/webp': 'webp',
  'image/gif': 'gif',
  'image/avif': 'avif',
};

const ALLOWED_EXTENSIONS = new Set(['.jpg', '.jpeg', '.png', '.webp', '.gif', '.avif']);

const MAX_FILE_SIZE_BYTES =
  Number(process.env.MAX_FILE_SIZE_MB ?? 10) * 1024 * 1024;

export interface ValidationResult {
  valid: boolean;
  format?: ImageFormat;
  error?: string;
}

/**
 * Validate an image URL for security and basic sanity before downloading.
 * Does NOT download the image — just validates the URL structure.
 */
export function validateImageUrl(rawUrl: string): ValidationResult {
  let url: URL;
  try {
    url = new URL(rawUrl);
  } catch {
    return { valid: false, error: 'Invalid URL' };
  }

  if (!['https:', 'http:'].includes(url.protocol)) {
    return { valid: false, error: 'Only http/https URLs are supported' };
  }

  // SSRF protection — block private/loopback addresses
  const host = url.hostname.toLowerCase();
  const blockedHosts = ['localhost', '127.0.0.1', '0.0.0.0', '::1'];
  if (
    blockedHosts.includes(host) ||
    /^10\./.test(host) ||
    /^192\.168\./.test(host) ||
    /^172\.(1[6-9]|2\d|3[01])\./.test(host)
  ) {
    return { valid: false, error: 'Private/localhost addresses are not permitted' };
  }

  return { valid: true };
}

/**
 * Validate Content-Type header from a fetch response.
 */
export function validateContentType(contentType: string | null): ValidationResult {
  if (!contentType) return { valid: false, error: 'No Content-Type header' };
  const mime = contentType.split(';')[0].trim().toLowerCase();
  const format = ALLOWED_CONTENT_TYPES[mime];
  if (!format) {
    return { valid: false, error: `Unsupported content type: ${mime}` };
  }
  return { valid: true, format };
}

/**
 * Validate file extension from a URL path.
 */
export function validateExtension(urlPath: string): ImageFormat | undefined {
  const lastSegment = urlPath.split('/').pop() ?? '';
  const ext = ('.' + lastSegment.split('.').pop()?.toLowerCase()) as string;
  return ALLOWED_EXTENSIONS.has(ext)
    ? (ALLOWED_CONTENT_TYPES[`image/${ext.slice(1)}`] ?? undefined)
    : undefined;
}

/**
 * Validate downloaded buffer size.
 */
export function validateFileSize(byteLength: number): ValidationResult {
  if (byteLength > MAX_FILE_SIZE_BYTES) {
    return {
      valid: false,
      error: `File too large: ${Math.round(byteLength / 1024 / 1024)}MB exceeds ${Math.round(MAX_FILE_SIZE_BYTES / 1024 / 1024)}MB limit`,
    };
  }
  return { valid: true };
}
