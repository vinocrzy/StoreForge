# Scalability & Performance Strategy

## Overview

This document outlines strategies for scaling the e-commerce platform from initial deployment to high-traffic production environments supporting thousands of concurrent users and millions of products.

## Scaling Dimensions

### Vertical Scaling (Scale Up)
Increase resources of existing servers:
- More CPU cores
- More RAM
- Faster storage (NVMe SSDs)
- Better network connectivity

**Use Cases**: Initial growth phase, cost-effective for moderate traffic

### Horizontal Scaling (Scale Out)
Add more servers to distribute load:
- Multiple API servers behind load balancer
- Database read replicas
- Distributed cache servers
- Queue workers across multiple machines

**Use Cases**: High-traffic scenarios, better fault tolerance

## Architecture Evolution

### Phase 1: Single Server (0-1K concurrent users)

```
┌─────────────────────────────────┐
│      Single Server              │
│  ┌──────────────────────────┐  │
│  │   Laravel Application    │  │
│  └──────────────────────────┘  │
│  ┌──────────────────────────┐  │
│  │   MySQL Database         │  │
│  └──────────────────────────┘  │
│  ┌──────────────────────────┐  │
│  │   Redis Cache/Queue      │  │
│  └──────────────────────────┘  │
└─────────────────────────────────┘
```

**Specifications**:
- 4 vCPU, 8GB RAM
- 100GB SSD storage
- Cost: ~$40-80/month

### Phase 2: Separated Services (1K-10K concurrent users)

```
┌──────────────────┐    ┌──────────────────┐
│  Load Balancer   │───▶│  App Server 1    │
│    (Nginx)       │    │    (Laravel)     │
└──────────────────┘    └──────────────────┘
           │            ┌──────────────────┐
           └───────────▶│  App Server 2    │
                        │    (Laravel)     │
                        └──────────────────┘
                                │
        ┌───────────────────────┼───────────────────┐
        │                       │                   │
┌───────▼────────┐    ┌────────▼────────┐  ┌──────▼──────┐
│ MySQL Primary  │───▶│ MySQL Replica   │  │   Redis     │
│   (Master)     │    │    (Read)       │  │   Cluster   │
└────────────────┘    └─────────────────┘  └─────────────┘
        │
┌───────▼────────┐
│ Queue Workers  │
│  (Multiple)    │
└────────────────┘
```

**Specifications**:
- App Servers: 2-4 instances (2 vCPU, 4GB RAM each)
- Database: Primary (4 vCPU, 16GB RAM) + Replica
- Redis: 2 vCPU, 4GB RAM
- Cost: ~$200-400/month

### Phase 3: Microservices-Ready (10K+ concurrent users)

```
┌─────────────────────────────────────────┐
│         CDN (CloudFlare/AWS)            │
└────────────────┬────────────────────────┘
                 │
┌────────────────▼────────────────────────┐
│      Load Balancer (Auto-scaling)       │
└────────────────┬────────────────────────┘
                 │
    ┌────────────┼────────────┐
    │            │            │
┌───▼──┐    ┌───▼──┐    ┌───▼──┐
│ API  │    │ API  │    │ API  │
│ Node │    │ Node │    │ Node │
└──────┘    └──────┘    └──────┘
    │            │            │
    └────────────┼────────────┘
                 │
    ┌────────────┼────────────┐
    │            │            │
┌───▼─────┐  ┌──▼───┐  ┌─────▼──────┐
│Database │  │Redis │  │  S3/MinIO  │
│ Cluster │  │Cluster│  │  Storage   │
└─────────┘  └──────┘  └────────────┘
    │
┌───▼────────────┐
│ Queue Workers  │
│ (Auto-scaling) │
└────────────────┘
```

## Database Scaling Strategies

### 1. Query Optimization

#### Indexing Strategy

```sql
-- Composite indexes for common queries
CREATE INDEX idx_products_store_status 
ON products(store_id, status, created_at);

CREATE INDEX idx_orders_store_status_date 
ON orders(store_id, status, created_at);

CREATE INDEX idx_customers_store_email 
ON customers(store_id, email);

-- Covering indexes for frequent read patterns
CREATE INDEX idx_products_list 
ON products(store_id, status, featured, created_at) 
INCLUDE (name, slug, price, compare_at_price);
```

#### Query Analysis

```php
// Enable query logging
DB::enableQueryLog();

// Your code here

// Analyze queries
$queries = DB::getQueryLog();
foreach ($queries as $query) {
    if ($query['time'] > 100) {  // Log slow queries (>100ms)
        Log::warning('Slow query detected', [
            'sql' => $query['query'],
            'time' => $query['time'],
            'bindings' => $query['bindings'],
        ]);
    }
}
```

#### N+1 Query Prevention

```php
// Bad - N+1 problem
$orders = Order::all();
foreach ($orders as $order) {
    echo $order->customer->name;  // Additional query per order
    foreach ($order->items as $item) {  // More queries
        echo $item->product->name;
    }
}

// Good - Eager loading
$orders = Order::with([
    'customer',
    'items.product',
    'items.product.images'
])->get();
```

### 2. Read Replicas

**Laravel Configuration**:

```php
// config/database.php
'mysql' => [
    'read' => [
        'host' => [
            env('DB_READ_HOST_1', '127.0.0.1'),
            env('DB_READ_HOST_2', '127.0.0.1'),
        ],
    ],
    'write' => [
        'host' => [
            env('DB_WRITE_HOST', '127.0.0.1'),
        ],
    ],
    'sticky' => true,  // Read your writes
    'driver' => 'mysql',
    'database' => env('DB_DATABASE', 'ecommerce'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
],
```

**Usage**:

```php
// Explicitly use read connection
$products = DB::connection('mysql::read')->table('products')->get();

// Force write connection
$product = DB::connection('mysql::write')->table('products')->find($id);
```

### 3. Database Sharding (Advanced)

**Horizontal Partitioning by Store ID**:

```
Shard 1: Stores 1-1000
Shard 2: Stores 1001-2000
Shard 3: Stores 2001-3000
```

**Dynamic Connection Management**:

```php
public function getConnectionForStore(int $storeId): string
{
    $shardNumber = ceil($storeId / 1000);
    return "mysql_shard_{$shardNumber}";
}

// Use appropriate shard
$connection = $this->getConnectionForStore($storeId);
$products = DB::connection($connection)->table('products')
    ->where('store_id', $storeId)
    ->get();
```

## Caching Strategy

### 1. Cache Layers

#### Application Cache (Redis)

```php
// Cache product details
Cache::remember("product:{$id}", 3600, function () use ($id) {
    return Product::with(['categories', 'images', 'inventory'])->find($id);
});

// Cache category tree
Cache::remember("categories:tree:{$storeId}", 86400, function () use ($storeId) {
    return Category::where('store_id', $storeId)
        ->with('children')
        ->whereNull('parent_id')
        ->get();
});

// Cache expensive calculations
Cache::remember("analytics:sales:{$storeId}:{$date}", 3600, function () use ($storeId, $date) {
    return $this->calculateSalesMetrics($storeId, $date);
});
```

#### Query Result Cache

```php
// Cache query results
public function getActiveProducts(int $storeId): Collection
{
    returnCache::tags(['products', "store:{$storeId}"])
        ->remember("products:active:{$storeId}", 1800, function () use ($storeId) {
            return Product::where('store_id', $storeId)
                ->where('status', 'active')
                ->with(['categories', 'images'])
                ->get();
        });
}

// Invalidate cache on update
public function updateProduct(Product $product, array $data): void
{
    $product->update($data);
    
    Cache::tags(['products', "store:{$product->store_id}"])->flush();
}
```

#### HTTP Cache (CDN)

```php
// Set cache headers for API responses
return response()->json($data)
    ->header('Cache-Control', 'public, max-age=3600')
    ->header('ETag', md5(json_encode($data)))
    ->header('Last-Modified', $product->updated_at->toRfc7231String());
```

### 2. Cache Invalidation Strategies

#### Time-Based Expiration

```php
// Short TTL for frequently changing data
Cache::put('inventory:' . $productId, $quantity, 60);  // 1 minute

// Long TTL for stable data
Cache::put('store:settings:' . $storeId, $settings, 86400);  // 24 hours
```

#### Event-Based Invalidation

```php
// Listen to model events
class Product extends Model
{
    protected static function booted(): void
    {
        static::updated(function ($product) {
            Cache::tags(['products', "product:{$product->id}"])->flush();
            Cache::forget("product:{$product->id}");
        });
        
        static::deleted(function ($product) {
            Cache::tags(['products', "product:{$product->id}"])->flush();
        });
    }
}
```

### 3. Full-Page Caching

For static pages on storefront:

```php
// Middleware for full-page cache
class CacheResponse
{
    public function handle(Request $request, Closure $next)
    {
        $key = 'page:' . $request->url();
        
        if ($cached = Cache::get($key)) {
            return response($cached);
        }
        
        $response = $next($request);
        
        if ($response->isSuccessful()) {
            Cache::put($key, $response->getContent(), 3600);
        }
        
        return $response;
    }
}
```

## Queue & Background Job Optimization

### 1. Queue Configuration

```php
// config/queue.php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
    ],
    
    'high-priority' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'high',
        'retry_after' => 90,
    ],
],
```

### 2. Job Prioritization

```php
// High priority - User-facing operations
ProcessPayment::dispatch($order)->onQueue('high');
SendOrderConfirmation::dispatch($order)->onQueue('high');

// Normal priority - Standard operations
UpdateInventory::dispatch($product)->onQueue('default');
GenerateInvoice::dispatch($order)->onQueue('default');

// Low priority - Non-urgent tasks
GenerateReport::dispatch($reportId)->onQueue('low');
CleanupOldData::dispatch()->onQueue('low');
```

### 3. Job Batching

```php
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;

$batch = Bus::batch([
    new ProcessCsvImportJob($file, 0, 1000),
    new ProcessCsvImportJob($file, 1000, 2000),
    new ProcessCsvImportJob($file, 2000, 3000),
])
->then(function (Batch $batch) {
    // All jobs completed successfully
    Log::info('Batch import completed', ['batch_id' => $batch->id]);
})
->catch(function (Batch $batch, Throwable $e) {
    // First batch job failure detected
    Log::error('Batch import failed', ['error' => $e->getMessage()]);
})
->finally(function (Batch $batch) {
    // Batch has finished executing
})
->dispatch();
```

### 4. Worker Scaling

```bash
# Multiple workers for different queues
php artisan queue:work redis --queue=high --tries=3 --timeout=30 &
php artisan queue:work redis --queue=default --tries=3 --timeout=60 &
php artisan queue:work redis --queue=low --tries=2 --timeout=120 &

# Using Supervisor for process management
[program:laravel-worker-high]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work redis --queue=high --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
numprocs=4
```

## API Performance Optimization

### 1. Response Pagination

```php
public function index(Request $request): JsonResponse
{
    $perPage = min($request->get('per_page', 15), 100);  // Max 100
    
    $products = Product::query()
        ->where('store_id', $this->tenantContext->getCurrentStoreId())
        ->where('status', 'active')
        ->paginate($perPage);
    
    return response()->json($products);
}
```

### 2. Selective Field Loading

```php
// Only load required fields
$products = Product::select(['id', 'name', 'slug', 'price', 'status'])
    ->where('store_id', $storeId)
    ->get();

// Include relationships selectively via API
public function index(Request $request): JsonResponse
{
    $query = Product::query();
    
    if ($request->has('include')) {
        $includes = explode(',', $request->get('include'));
        $query->with($includes);
    }
    
    return response()->json($query->get());
}
```

### 3. API Response Compression

```php
// middleware
class CompressResponse
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        if (in_array('gzip', $request->getEncodings())) {
            $response->setContent(gzencode($response->getContent(), 9));
            $response->header('Content-Encoding', 'gzip');
        }
        
        return $response;
    }
}
```

### 4. Rate Limiting

```php
// routes/api.php
Route::middleware(['throttle:60,1'])->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
});

// Custom rate limits per tenant
RateLimiter::for('api', function (Request $request) {
    $storeId = $request->header('X-Store-ID');
    $limits = [
        'free' => 60,
        'basic' => 120,
        'premium' => 300,
    ];
    
    $plan = Store::find($storeId)?->subscription_plan ?? 'free';
    return Limit::perMinute($limits[$plan]);
});
```

## CDN & Static Asset Optimization

### 1. Asset Compilation

```bash
# Optimize production build
npm run build

# Output minified, chunked assets
public/build/
├── assets/
│   ├── app-[hash].js      # 150KB → 45KB gzipped
│   ├── vendor-[hash].js   # 500KB → 120KB gzipped
│   └── app-[hash].css     # 80KB → 15KB gzipped
```

### 2. Image Optimization

```php
// Automatic image optimization on upload
use Spatie\MediaLibrary\MediaCollections\Models\Media;

public function registerMediaConversions(Media $media = null): void
{
    $this->addMediaConversion('thumb')
        ->width(150)
        ->height(150)
        ->format('webp')
        ->quality(80);
    
    $this->addMediaConversion('medium')
        ->width(600)
        ->height(600)
        ->format('webp')
        ->quality(85);
    
    $this->addMediaConversion('large')
        ->width(1200)
        ->height(1200)
        ->format('webp')
        ->quality(90);
}
```

### 3. CDN Configuration

```php
// config/filesystems.php
'cloudfront' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
    'url' => env('AWS_CLOUDFRONT_URL'),  // CDN URL
    'options' => [
        'CacheControl' => 'max-age=31536000, public',
    ],
],
```

## Monitoring & Performance Tracking

### 1. Application Performance Monitoring

```php
// Track slow operations
$startTime = microtime(true);

// ... expensive operation

$duration = (microtime(true) - $startTime) * 1000;

if ($duration > 1000) {  // > 1 second
    Log::warning('Slow operation detected', [
        'operation' => 'product_search',
        'duration_ms' => $duration,
        'params' => $params,
    ]);
}
```

### 2. Key Metrics to Monitor

- **Response Time**: P50, P95, P99 percentiles
- **Throughput**: Requests per second
- **Error Rate**: 4xx and 5xx responses
- **Database**: Query time, connection pool usage
- **Cache**: Hit/miss ratio, memory usage
- **Queue**: Job processing time, failure rate
- **Resource**: CPU, memory, disk I/O

### 3. Tools

- **Laravel Telescope**: Development debugging
- **Laravel Horizon**: Queue monitoring
- **New Relic / Datadog**: APM
- **Grafana + Prometheus**: Metrics visualization
- **Sentry**: Error tracking

## Load Testing

### Apache Bench

```bash
# Test API endpoint
ab -n 1000 -c 100 -H "Authorization: Bearer TOKEN" \
   https://api.yourplatform.com/v1/admin/products
```

### K6 Load Testing

```javascript
import http from 'k6/http';
import { check, sleep } from 'k6';

export let options = {
  stages: [
    { duration: '2m', target: 100 },   // Ramp-up to 100 users
    { duration: '5m', target: 100 },   // Stay at 100 users
    { duration: '2m', target: 200 },   // Ramp-up to 200 users
    { duration: '5m', target: 200 },   // Stay at 200 users
    { duration: '2m', target: 0 },     // Ramp-down to 0 users
  ],
};

export default function () {
  let response = http.get('https://api.yourplatform.com/v1/storefront/products');
  
  check(response, {
    'status is 200': (r) => r.status === 200,
    'response time < 500ms': (r) => r.timings.duration < 500,
  });
  
  sleep(1);
}
```

## Cost Optimization

### 1. Resource Right-Sizing

- Monitor actual resource usage
- Scale down during off-peak hours
- Use spot instances for queue workers

### 2. Cache Aggressively

- Reduce database queries by 80-90%
- Lower API response times
- Reduce server load

### 3. Efficient Storage

- Use object storage (S3) for media
- Compress images and assets
- Implement lifecycle policies for old data

## Next Steps

1. Review [Security Guidelines](09-security.md)
2. Review [Development Roadmap](10-development-roadmap.md)
3. Set up monitoring and alerting
4. Implement caching strategy
5. Configure CDN
