// Asset manifest — serialised to ASSETS_DIR/manifest.json

import { readFile, writeFile, mkdir } from 'node:fs/promises';
import { join } from 'node:path';
import type { AssetManifest, AssetRecord } from './types.js';

export function getAssetsDir(): string {
  return process.env.ASSETS_DIR ?? join(process.cwd(), 'assets');
}

function manifestPath(): string {
  return join(getAssetsDir(), 'manifest.json');
}

const EMPTY_MANIFEST: AssetManifest = {
  version: '1.0.0',
  lastUpdated: new Date().toISOString(),
  totalAssets: 0,
  assets: {},
};

export async function readManifest(): Promise<AssetManifest> {
  try {
    const raw = await readFile(manifestPath(), 'utf-8');
    return JSON.parse(raw) as AssetManifest;
  } catch {
    return structuredClone(EMPTY_MANIFEST);
  }
}

export async function writeManifest(manifest: AssetManifest): Promise<void> {
  await mkdir(getAssetsDir(), { recursive: true });
  manifest.lastUpdated = new Date().toISOString();
  manifest.totalAssets = Object.keys(manifest.assets).length;
  await writeFile(manifestPath(), JSON.stringify(manifest, null, 2), 'utf-8');
}

export async function addAsset(record: AssetRecord): Promise<void> {
  const manifest = await readManifest();
  manifest.assets[record.id] = record;
  await writeManifest(manifest);
}

export async function updateAsset(id: string, updates: Partial<AssetRecord>): Promise<void> {
  const manifest = await readManifest();
  if (!manifest.assets[id]) throw new Error(`Asset ${id} not found`);
  manifest.assets[id] = { ...manifest.assets[id], ...updates, updatedAt: new Date().toISOString() };
  await writeManifest(manifest);
}

export async function removeAsset(id: string): Promise<AssetRecord> {
  const manifest = await readManifest();
  const record = manifest.assets[id];
  if (!record) throw new Error(`Asset ${id} not found`);
  delete manifest.assets[id];
  await writeManifest(manifest);
  return record;
}

export async function getAssetById(id: string): Promise<AssetRecord | undefined> {
  const manifest = await readManifest();
  return manifest.assets[id];
}

export async function getAssetsByCategory(category?: string): Promise<AssetRecord[]> {
  const manifest = await readManifest();
  const all = Object.values(manifest.assets);
  return category ? all.filter(a => a.category === category) : all;
}
