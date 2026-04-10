// Shared TypeScript types for mcp-asset-service

export type AssetCategory =
  | 'hero'
  | 'products'
  | 'categories'
  | 'placeholders'
  | 'generated'
  | string;

export type ImageFormat = 'jpg' | 'jpeg' | 'png' | 'webp' | 'gif' | 'avif';

export interface AssetRecord {
  id: string;
  filename: string;
  category: AssetCategory;
  originalUrl: string;
  localPath: string;       // relative to ASSETS_DIR
  fileSize: number;        // bytes
  width: number;
  height: number;
  format: ImageFormat;
  altText: string;
  tags: string[];
  optimized: boolean;
  createdAt: string;       // ISO 8601
  updatedAt: string;
}

export interface AssetManifest {
  version: string;
  lastUpdated: string;
  totalAssets: number;
  assets: Record<string, AssetRecord>;
}

export interface DownloadOptions {
  altText?: string;
  tags?: string[];
  optimize?: boolean;
}

export interface OptimizeOptions {
  quality?: number;        // 1–100
  maxWidth?: number;
  maxHeight?: number;
  format?: 'webp' | 'jpg' | 'png' | 'avif';
}
