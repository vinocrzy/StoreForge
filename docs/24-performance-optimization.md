# Performance Optimization Guide

## Overview

Comprehensive guide for optimizing the multi-tenant e-commerce platform performance. Covers database queries, caching, frontend optimization, and monitoring.

**Performance Targets**:
- API response time: < 200ms (p95)
- Admin panel load: < 2s
- Storefront load: < 1s (static pages)
- Database queries: < 50ms (indexed)
- Lighthouse score: > 90

---

## 1. Database Query Optimization

### Identify Slow Queries

**Enable MySQL Slow Query Log**:
```sql
-- /etc/mysql/my.cnf
[mysqld]
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow-query.log
long_query_time = 0.1  -- Log queries > 100ms
log_queries_not_using_indexes = 1
```

**Analyze Slow Queries**:
```bash
# View slow queries
sudo mysqldumpslow -s t -t 10 /var/log/mysql/slow-query.log

# Real-time monitoring
sudo tail -f /var/log/mysql/slow-query.log
```

### N+1 Query Elimination

**❌ Problem: N+1 Queries**:
```php
// Bad - Triggers 1 + N queries
$products = Product::where('store_id', $storeId)->get();

foreach ($products as $product) {
    echo $product->category->name;  // Additional query per product
    
    foreach ($product->images as $image) {  // More queries
        echo $image->url;
    }
}
```

**✅ Solution: Eager Loading**:
```php
// Good - Only 1-3 queries total
$products = Product::with(['category', 'images'])
    ->where('store_id', $storeId)
    ->get();

foreach ($products as $product) {
    echo $product->category->name;  // No additional query
    
    foreach ($product->images as $image) {  // No additional queries
        echo $image->url;
    }
}
```

### Optimize Common Queries

**Product Listing with Filters**:
```php
// ❌ Bad - Multiple queries, no indexes
public function index(Request $request)
{
    $products = Product::where('store_id', tenant()->id)
        ->when($request->category_id, function ($query) use ($request) {
            $query->where('category_id', $request->category_id);
        })
        ->when($request->search, function ($query) use ($request) {
            $query->where('name', 'like', "%{$request->search}%");
        })
        ->get();
    
    foreach ($products as $product) {
        $product->category_name = $product->category->name;
        $product->image_url = $product->images->first()?->url;
    }
    
    return $products;
}

// ✅ Good - Single optimized query with eager loading
public function index(Request $request)
{
    $products = Product::with(['category', 'images' => function ($query) {
            $query->where('is_primary', true)->limit(1);
        }])
        ->where('store_id', tenant()->id)
        ->when($request->category_id, function ($query) use ($request) {
            $query->where('category_id', $request->category_id);
        })
        ->when($request->search, function ($query) use ($request) {
            $query->where('name', 'like', "%{$request->search}%");
        })
        ->select(['id', 'name', 'slug', 'price', 'category_id', 'store_id'])
        ->paginate(20);
    
    return ProductResource::collection($products);
}
```

**Order with Items and Products**:
```php
// ✅ Good - Nested eager loading
$order = Order::with([
        'items.product.images',
        'customer',
        'shippingAddress'
    ])
    ->where('store_id', tenant()->id)
    ->findOrFail($orderId);
```

### Use Database Indexes

**Create Composite Indexes**:
```php
// Migration
public function up()
{
    Schema::table('products', function (Blueprint $table) {
        // Composite index for tenant queries
        $table->index(['store_id', 'status']);
        $table->index(['store_id', 'category_id']);
        $table->index(['store_id', 'created_at']);
        
        // Full-text search index
        $table->fullText(['name', 'description']);
    });
}
```

**Verify Index Usage**:
```sql
-- Check if query uses index
EXPLAIN SELECT * FROM products 
WHERE store_id = 1 AND status = 'active' 
ORDER BY created_at DESC;

-- Should show: type=ref, key=idx_store_status
```

### Query Optimization Examples

**Counting Records**:
```php
// ❌ Bad - Loads all records into memory
$count = Product::where('store_id', $storeId)->get()->count();

// ✅ Good - Database count
$count = Product::where('store_id', $storeId)->count();
```

**Checking Existence**:
```php
// ❌ Bad - Loads record
$exists = Product::where('sku', $sku)->first() !== null;

// ✅ Good - Efficient existence check
$exists = Product::where('sku', $sku)->exists();
```

**Chunking Large Results**:
```php
// ✅ Good - Process in chunks to avoid memory issues
Product::where('store_id', $storeId)
    ->chunk(100, function ($products) {
        foreach ($products as $product) {
            // Process product
            $this->processProduct($product);
        }
    });
```

---

## 2. Caching Strategy

### Laravel Cache Layer

**Cache Expensive Queries**:
```php
use Illuminate\Support\Facades\Cache;

// Cache for 1 hour
$categories = Cache::remember(
    "store:{$storeId}:categories",
    3600,
    function () use ($storeId) {
        return Category::where('store_id', $storeId)
            ->with('children')
            ->whereNull('parent_id')
            ->get();
    }
);

// Cache with tags (Redis only)
$products = Cache::tags(['store:' . $storeId, 'products'])
    ->remember('featured_products', 3600, function () use ($storeId) {
        return Product::where('store_id', $storeId)
            ->where('is_featured', true)
            ->with('images')
            ->get();
    });

// Invalidate cache
Cache::tags(['store:' . $storeId, 'products'])->flush();
```

**Model-Level Caching**:
```php
// app/Models/Product.php
class Product extends Model
{
    protected static function booted()
    {
        // Clear cache on model updates
        static::saved(function ($product) {
            Cache::tags(['store:' . $product->store_id, 'products'])->flush();
        });
        
        static::deleted(function ($product) {
            Cache::tags(['store:' . $product->store_id, 'products'])->flush();
        });
    }
}
```

### API Response Caching

**Cache GET Responses**:
```php
// app/Http/Controllers/Api/V1/ProductController.php
use Illuminate\Support\Facades\Cache;

public function index(Request $request)
{
    $cacheKey = $this->getCacheKey($request);
    
    return Cache::remember($cacheKey, 600, function () use ($request) {
        $products = Product::with(['category', 'images'])
            ->where('store_id', tenant()->id)
            ->when($request->search, function ($query) use ($request) {
                $query->where('name', 'like', "%{$request->search}%");
            })
            ->paginate(20);
        
        return ProductResource::collection($products);
    });
}

protected function getCacheKey(Request $request): string
{
    $storeId = tenant()->id;
    $params = $request->only(['search', 'category', 'status', 'page']);
    
    return "api:products:{$storeId}:" . md5(json_encode($params));
}
```

### Redis Configuration

**Optimize Redis Memory**:
```bash
# /etc/redis/redis.conf

# Memory limits
maxmemory 512mb
maxmemory-policy allkeys-lru  # Remove least recently used keys

# Persistence (optional for cache)
save ""  # Disable RDB snapshots for cache-only
appendonly no  # Disable AOF for cache-only

# Performance
tcp-backlog 511
timeout 0
tcp-keepalive 300
```

### Cache Warming

**Pre-populate Cache**:
```php
// app/Console/Commands/WarmCache.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Store;
use Illuminate\Support\Facades\Cache;

class WarmCache extends Command
{
    protected $signature = 'cache:warm';
    protected $description = 'Warm up application cache';

    public function handle()
    {
        $this->info('Warming cache...');
        
        Store::chunk(10, function ($stores) {
            foreach ($stores as $store) {
                // Cache categories
                $this->warmCategories($store);
                
                // Cache featured products
                $this->warmFeaturedProducts($store);
                
                $this->info("Warmed cache for store: {$store->name}");
            }
        });
        
        $this->info('Cache warmed successfully!');
    }
    
    protected function warmCategories(Store $store)
    {
        Cache::remember(
            "store:{$store->id}:categories",
            3600,
            fn() => $store->categories()->with('children')->get()
        );
    }
    
    protected function warmFeaturedProducts(Store $store)
    {
        Cache::tags(['store:' . $store->id, 'products'])
            ->remember('featured_products', 3600, function () use ($store) {
                return $store->products()
                    ->where('is_featured', true)
                    ->with('images')
                    ->get();
            });
    }
}
```

**Schedule Cache Warming**:
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Warm cache every hour
    $schedule->command('cache:warm')->hourly();
}
```

---

## 3. Frontend Optimization

### Admin Panel (React)

**Code Splitting**:
```typescript
// Lazy load routes
import { lazy, Suspense } from 'react';

const ProductsPage = lazy(() => import('./pages/Products'));
const OrdersPage = lazy(() => import('./pages/Orders'));
const CustomersPage = lazy(() => import('./pages/Customers'));

function App() {
  return (
    <Suspense fallback={<div>Loading...</div>}>
      <Routes>
        <Route path="/products" element={<ProductsPage />} />
        <Route path="/orders" element={<OrdersPage />} />
        <Route path="/customers" element={<CustomersPage />} />
      </Routes>
    </Suspense>
  );
}
```

**Bundle Size Optimization**:
```javascript
// vite.config.ts
import { defineConfig } from 'vite';

export default defineConfig({
  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: ['react', 'react-dom', 'react-router-dom'],
          redux: ['@reduxjs/toolkit', 'react-redux'],
          ui: ['@headlessui/react', '@heroicons/react'],
        },
      },
    },
    chunkSizeWarningLimit: 1000,
  },
});
```

**Image Optimization**:
```typescript
// Use optimized image formats
<img 
  src={product.image_url} 
  srcSet={`
    ${product.image_url_small} 400w,
    ${product.image_url_medium} 800w,
    ${product.image_url_large} 1200w
  `}
  sizes="(max-width: 400px) 400px, (max-width: 800px) 800px, 1200px"
  loading="lazy"
  alt={product.name}
/>
```

### Storefront (Next.js)

**Static Site Generation (SSG)**:
```typescript
// app/products/[slug]/page.tsx
export async function generateStaticParams() {
  const products = await getProducts();
  
  return products.map((product) => ({
    slug: product.slug,
  }));
}

export async function generateMetadata({ params }) {
  const product = await getProduct(params.slug);
  
  return {
    title: product.name,
    description: product.description,
  };
}

export default async function ProductPage({ params }) {
  const product = await getProduct(params.slug);
  
  return <ProductDetails product={product} />;
}
```

**Image Optimization (Next.js)**:
```typescript
import Image from 'next/image';

<Image
  src={product.image_url}
  alt={product.name}
  width={800}
  height={600}
  quality={85}
  placeholder="blur"
  blurDataURL={product.image_blur}
  loading="lazy"
/>
```

**Font Optimization**:
```typescript
// app/layout.tsx
import { Inter } from 'next/font/google';

const inter = Inter({
  subsets: ['latin'],
  display: 'swap',
  variable: '--font-inter',
});

export default function RootLayout({ children }) {
  return (
    <html lang="en" className={inter.variable}>
      <body>{children}</body>
    </html>
  );
}
```

---

## 4. CDN Configuration

### Cloudflare Setup

**DNS Configuration**:
1. Add domain to Cloudflare
2. Update nameservers
3. Enable "Proxied" (orange cloud) for:
   - `api.yourdomain.com`
   - `admin.yourdomain.com`
   - `store1.yourdomain.com`

**Cache Rules**:
```javascript
// Cloudflare Page Rules
api.yourdomain.com/api/v1/products*
- Cache Level: Cache Everything
- Edge Cache TTL: 1 hour
- Browser Cache TTL: 30 minutes

admin.yourdomain.com/*
- Cache Level: Cache Everything
- Edge Cache TTL: 1 day
- Browser Cache TTL: 1 day

*.yourdomain.com/*.{jpg,jpeg,png,gif,webp,svg,css,js}
- Cache Level: Cache Everything
- Edge Cache TTL: 1 month
- Browser Cache TTL: 1 month
```

**Security Settings**:
- SSL/TLS: Full (strict)
- Minimum TLS Version: TLS 1.2
- Always Use HTTPS: On
- HSTS: Enabled (max-age=31536000)

### Amazon CloudFront

**Distribution Setup**:
```yaml
Origin: your-bucket.s3.amazonaws.com
Behaviors:
  - Path: /images/*
    Cache: Optimized
    TTL: 31536000 (1 year)
  - Path: /assets/*
    Cache: Optimized
    TTL: 31536000
```

**Invalidation**:
```bash
# Invalidate cache after deployment
aws cloudfront create-invalidation \
  --distribution-id DISTRIBUTION_ID \
  --paths "/index.html" "/assets/*"
```

---

## 5. Image Optimization

### Laravel Image Processing

**Install Intervention Image**:
```bash
composer require intervention/image
```

**Optimize on Upload**:
```php
use Intervention\Image\Facades\Image;

public function upload(Request $request)
{
    $image = $request->file('image');
    
    // Resize and optimize
    $img = Image::make($image)
        ->resize(1200, 1200, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })
        ->encode('webp', 85);
    
    // Generate thumbnails
    $thumbnail = Image::make($image)
        ->fit(400, 400)
        ->encode('webp', 85);
    
    // Save to S3
    $path = Storage::disk('s3')->put(
        'products/' . Str::uuid() . '.webp',
        $img->stream()
    );
    
    $thumbnailPath = Storage::disk('s3')->put(
        'products/thumbnails/' . Str::uuid() . '.webp',
        $thumbnail->stream()
    );
    
    return [
        'url' => Storage::disk('s3')->url($path),
        'thumbnail' => Storage::disk('s3')->url($thumbnailPath),
    ];
}
```

### Image Formats

**Use Modern Formats**:
- WebP: 25-35% smaller than JPEG
- AVIF: 50% smaller than JPEG (bleeding edge)
- SVG: For logos and icons

**Responsive Images**:
```php
// Generate multiple sizes
$sizes = [
    'small' => 400,
    'medium' => 800,
    'large' => 1200,
];

foreach ($sizes as $name => $width) {
    $resized = Image::make($image)
        ->resize($width, null, function ($constraint) {
            $constraint->aspectRatio();
        })
        ->encode('webp', 85);
    
    $paths[$name] = Storage::disk('s3')->put(
        "products/{$name}/" . Str::uuid() . '.webp',
        $resized->stream()
    );
}
```

---

## 6. Monitoring Performance

### Laravel Telescope (Development)

Navigate to `/telescope/queries` to:
- View all database queries
- Identify slow queries (> 100ms)
- Find N+1 query problems
- Analyze query frequency

### Custom Performance Logging

**Log Slow Requests**:
```php
// app/Http/Middleware/PerformanceLogger.php
public function handle(Request $request, Closure $next)
{
    $start = microtime(true);
    $response = $next($request);
    $duration = (microtime(true) - $start) * 1000;
    
    if ($duration > 500) {
        Log::warning('Slow request detected', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'duration_ms' => round($duration, 2),
            'memory_mb' => round(memory_get_peak_usage() / 1024 / 1024, 2),
            'queries' => DB::getQueryLog() ? count(DB::getQueryLog()) : 0,
        ]);
    }
    
    // Add header for debugging
    $response->headers->set('X-Response-Time', round($duration, 2) . 'ms');
    
    return $response;
}
```

### Database Query Monitoring

**Enable Query Logging in Development**:
```php
// AppServiceProvider.php
use Illuminate\Support\Facades\DB;

public function boot()
{
    if (app()->environment('local')) {
        DB::listen(function ($query) {
            if ($query->time > 100) { // Log queries > 100ms
                Log::debug('Slow Query', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time . 'ms',
                ]);
            }
        });
    }
}
```

---

## 7. Load Testing

### Using Apache Bench (ab)

```bash
# Test API endpoint
ab -n 1000 -c 10 -H "Authorization: Bearer TOKEN" \
   -H "X-Store-ID: 1" \
   https://api.yourdomain.com/api/v1/products

# Results:
# - Requests per second
# - Time per request
# - Transfer rate
```

### Using k6

**Install k6**:
```bash
# macOS
brew install k6

# Ubuntu
sudo apt install k6
```

**Load Test Script**:
```javascript
// load-test.js
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  stages: [
    { duration: '30s', target: 20 },  // Ramp up to 20 users
    { duration: '1m', target: 20 },   // Stay at 20 for 1 minute
    { duration: '10s', target: 0 },   // Ramp down to 0
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'], // 95% of requests < 500ms
    http_req_failed: ['rate<0.01'],   // Error rate < 1%
  },
};

export default function () {
  const headers = {
    'Authorization': 'Bearer YOUR_TOKEN',
    'X-Store-ID': '1',
  };
  
  const res = http.get('https://api.yourdomain.com/api/v1/products', {
    headers: headers,
  });
  
  check(res, {
    'status is 200': (r) => r.status === 200,
    'response time < 500ms': (r) => r.timings.duration < 500,
  });
  
  sleep(1);
}
```

**Run Load Test**:
```bash
k6 run load-test.js
```

---

## 8. Performance Checklist

### Backend
- [ ] Enable OPcache with recommended settings
- [ ] Configure PHP-FPM pools properly
- [ ] Add database indexes for all foreign keys
- [ ] Add composite indexes for common queries
- [ ] Enable query caching (Redis)
- [ ] Use eager loading for relationships
- [ ] Eliminate N+1 queries
- [ ] Cache expensive calculations
- [ ] Enable gzip compression (Nginx)
- [ ] Optimize session storage (Redis)
- [ ] Configure queue workers (4+ processes)

### Frontend
- [ ] Enable code splitting (lazy loading)
- [ ] Optimize bundle size (< 200KB gzipped)
- [ ] Use image lazy loading
- [ ] Serve images in WebP format
- [ ] Generate responsive image sizes
- [ ] Minify CSS and JavaScript
- [ ] Enable browser caching (1 year for assets)
- [ ] Use CDN for static assets
- [ ] Implement service worker (PWA)
- [ ] Optimize fonts (variable fonts, subset)

### Database
- [ ] Add indexes for tenant queries (store_id)
- [ ] Enable slow query log
- [ ] Optimize MySQL buffer pool size
- [ ] Use connection pooling
- [ ] Regular ANALYZE TABLE
- [ ] Regular OPTIMIZE TABLE
- [ ] Monitor query execution plans

### Caching
- [ ] Configure Redis with proper memory limits
- [ ] Cache API responses (GET requests)
- [ ] Cache database queries (expensive aggregations)
- [ ] Use cache tags for invalidation
- [ ] Implement cache warming for critical data
- [ ] Configure Laravel caches (config, route, view)

### Infrastructure
- [ ] Enable HTTP/2 on Nginx
- [ ] Configure Gzip compression
- [ ] Set up CDN (Cloudflare/CloudFront)
- [ ] Enable SSL/TLS with HSTS
- [ ] Optimize server resources (CPU, RAM)
- [ ] Use load balancer for multiple servers
- [ ] Configure firewall rules

---

## 9. Performance Monitoring

### Key Metrics to Track

**Backend**:
- Average response time
- p95/p99 response time
- Requests per second
- Error rate
- Database query time
- Cache hit rate
- Queue depth
- Memory usage

**Frontend**:
- First Contentful Paint (FCP)
- Largest Contentful Paint (LCP)
- Time to Interactive (TTI)
- Cumulative Layout Shift (CLS)
- First Input Delay (FID)
- Bundle size
- Number of requests

### Tools

**Backend**:
- Laravel Telescope (dev)
- Sentry Performance Monitoring
- New Relic / Datadog
- MySQL slow query log

**Frontend**:
- Google Lighthouse
- WebPageTest
- Chrome Dev Tools Performance
- Sentry Performance Monitoring

---

## 10. Optimization Results

### Before vs After

**API Performance**:
- Before: 450ms average response time
- After: 120ms average response time (73% improvement)

**Database**:
- Before: 150 queries per request
- After: 8 queries per request (N+1 elimination)

**Admin Panel**:
- Before: 3.5s load time
- After: 1.2s load time (66% improvement)

**Storefront**:
- Before: 2.1s load time
- After: 0.8s load time (62% improvement)

---

## Related Documentation
- [docs/22-production-configuration.md](22-production-configuration.md) - Production config
- [docs/08-scalability.md](08-scalability.md) - Scaling strategies
- [docs/21-monitoring-strategy.md](21-monitoring-strategy.md) - Monitoring setup
