// Shared TypeScript types for mcp-browser-service

export interface ImageResult {
  url: string;
  thumbnailUrl: string;
  alt: string;
  width: number;
  height: number;
  source: string;
  pageTitle?: string;
}

export interface PageExtract {
  url: string;
  title: string;
  description: string;
  ogImage?: string;
  images: ImageResult[];
  headings: string[];
  layoutSections: string[];
}

export interface SearchOptions {
  limit?: number;
  minWidth?: number;
  minHeight?: number;
  allowedDomains?: string[];
  source?: 'unsplash' | 'pexels' | 'auto';
}

export interface GetImagesOptions extends SearchOptions {
  deduplicate?: boolean;
}
