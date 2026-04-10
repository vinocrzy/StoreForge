// Simple in-memory TTL cache for search results

interface Entry<T> {
  value: T;
  expiresAt: number;
}

class TTLCache<T> {
  private readonly store = new Map<string, Entry<T>>();
  private readonly ttlMs: number;

  constructor(ttlSeconds: number) {
    this.ttlMs = ttlSeconds * 1000;
  }

  get(key: string): T | undefined {
    const entry = this.store.get(key);
    if (!entry) return undefined;
    if (Date.now() > entry.expiresAt) {
      this.store.delete(key);
      return undefined;
    }
    return entry.value;
  }

  set(key: string, value: T): void {
    this.store.set(key, { value, expiresAt: Date.now() + this.ttlMs });
  }

  clear(): void {
    this.store.clear();
  }

  size(): number {
    return this.store.size;
  }
}

const TTL = Number(process.env.CACHE_TTL ?? 3600);

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export const searchCache = new TTLCache<any>(TTL);
