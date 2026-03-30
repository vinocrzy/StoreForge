# Multi-Tenancy Strategy

## Overview

The platform uses a **single database, shared schema** approach with tenant isolation enforced at the application level. This provides the best balance between development simplicity, cost-effectiveness, and scalability for a multi-store e-commerce platform.

## Architecture Pattern

### Single Database with Tenant Isolation

**Approach**: All stores share the same database and tables, with each record tagged with a `store_id` column.

**Benefits**:
- Lower infrastructure costs
- Easier migrations and schema changes
- Simplified backup and recovery
- Efficient resource utilization
- Cross-tenant analytics possible

**Trade-offs**:
- Requires careful query filtering
- Potential security risk if not implemented correctly
- Limited per-tenant customization

## Implementation

### 1. Database Level Isolation

#### Schema Design

Every tenant-specific table includes `store_id`:

```sql
CREATE TABLE products (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,  -- Tenant identifier
    name VARCHAR(255) NOT NULL,
    ...
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    INDEX idx_store_id (store_id)
);
```

#### Composite Indexes

All queries should use composite indexes starting with `store_id`:

```sql
-- Efficient query pattern
INDEX idx_store_status (store_id, status)
INDEX idx_store_created (store_id, created_at)
```

This ensures queries are scoped to a single tenant and perform well.

### 2. Application Level Isolation

#### Laravel Global Scopes

Automatically filter all queries by tenant:

```php
<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use App\Services\Tenant\TenantContext;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if ($storeId = app(TenantContext::class)->getCurrentStoreId()) {
            $builder->where('store_id', $storeId);
        }
    }
}
```

**Apply to Models**:

```php
<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
        
        // Auto-assign store_id on creation
        static::creating(function ($model) {
            if (!$model->store_id) {
                $model->store_id = app(TenantContext::class)->getCurrentStoreId();
            }
        });
    }
}
```

#### Tenant Context Resolution

```php
<?php

namespace App\Services\Tenant;

use App\Models\Store;
use Illuminate\Support\Facades\Cache;

class TenantContext
{
    private ?int $currentStoreId = null;
    private ?Store $currentStore = null;

    public function setCurrentStore(int $storeId): void
    {
        $this->currentStoreId = $storeId;
        $this->currentStore = Cache::remember(
            "store:{$storeId}",
            3600,
            fn() => Store::find($storeId)
        );
    }

    public function getCurrentStoreId(): ?int
    {
        return $this->currentStoreId;
    }

    public function getCurrentStore(): ?Store
    {
        return $this->currentStore;
    }

    public function clearContext(): void
    {
        $this->currentStoreId = null;
        $this->currentStore = null;
    }
}
```

#### Tenant Resolution Middleware

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\Tenant\TenantContext;
use App\Models\Store;

class TenantContextMiddleware
{
    public function __construct(
        private TenantContext $tenantContext
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $storeId = $this->resolveStoreId($request);
        
        if (!$storeId) {
            return response()->json([
                'error' => 'Store not found'
            ], 404);
        }
        
        $this->tenantContext->setCurrentStore($storeId);
        
        return $next($request);
    }

    private function resolveStoreId(Request $request): ?int
    {
        // Method 1: From API header
        if ($storeId = $request->header('X-Store-ID')) {
            return (int) $storeId;
        }
        
        // Method 2: From authenticated user
        if ($user = $request->user()) {
            return $user->store_id;
        }
        
        // Method 3: From subdomain
        $host = $request->getHost();
        if (preg_match('/^(.+)\.yourplatform\.com$/', $host, $matches)) {
            $subdomain = $matches[1];
            $store = Store::where('subdomain', $subdomain)->first();
            return $store?->id;
        }
        
        // Method 4: From custom domain
        $store = Store::where('domain', $host)->first();
        return $store?->id;
        
        return null;
    }
}
```

### 3. API Level Isolation

#### Admin API

Tenant is resolved from authenticated user:

```php
// routes/api.php
Route::prefix('v1/admin')
    ->middleware(['auth:sanctum', 'tenant'])
    ->group(function () {
        Route::apiResource('products', ProductController::class);
        Route::apiResource('orders', OrderController::class);
    });
```

#### Storefront API

Tenant is resolved from domain/subdomain or header:

```php
// routes/api.php
Route::prefix('v1/storefront')
    ->middleware(['tenant'])
    ->group(function () {
        Route::get('products', [StorefrontProductController::class, 'index']);
        Route::get('products/{slug}', [StorefrontProductController::class, 'show']);
    });
```

### 4. Storage Isolation

#### File Storage Structure

```
storage/
├── stores/
│   ├── 1/                    # Store ID 1
│   │   ├── products/
│   │   │   ├── 1/
│   │   │   │   ├── image1.jpg
│   │   │   │   └── image2.jpg
│   │   │   └── 2/
│   │   ├── logos/
│   │   │   └── logo.png
│   │   └── temp/
│   ├── 2/                    # Store ID 2
│   │   └── ...
```

#### Laravel Storage Configuration

```php
// config/filesystems.php
'disks' => [
    'tenant' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
        'url' => env('AWS_URL'),
        'root' => function() {
            $storeId = app(\App\Services\Tenant\TenantContext::class)->getCurrentStoreId();
            return "stores/{$storeId}";
        },
    ],
],
```

### 5. Cache Isolation

#### Cache Key Prefixing

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use App\Services\Tenant\TenantContext;

class TenantAwareCache
{
    public function __construct(
        private TenantContext $tenantContext
    ) {}

    private function makeKey(string $key): string
    {
        $storeId = $this->tenantContext->getCurrentStoreId();
        return "store:{$storeId}:{$key}";
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::get($this->makeKey($key), $default);
    }

    public function put(string $key, mixed $value, int $ttl = 3600): bool
    {
        return Cache::put($this->makeKey($key), $value, $ttl);
    }

    public function forget(string $key): bool
    {
        return Cache::forget($this->makeKey($key));
    }

    public function flushStore(): void
    {
        $storeId = $this->tenantContext->getCurrentStoreId();
        // Use cache tags if available (Redis)
        Cache::tags(["store:{$storeId}"])->flush();
    }
}
```

### 6. Queue Jobs Isolation

#### Job Context Preservation

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Tenant\TenantContext;

class ProcessOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $orderId,
        private int $storeId  // Store context
    ) {}

    public function handle(TenantContext $tenantContext): void
    {
        // Set tenant context
        $tenantContext->setCurrentStore($this->storeId);
        
        // Process order with correct tenant context
        $order = Order::find($this->orderId);
        // ... business logic
    }
}

// Dispatch with tenant context
ProcessOrder::dispatch($order->id, $order->store_id);
```

## Security Measures

### 1. Query Verification

Always verify queries include `store_id` filter:

```php
// Good - Tenant-aware
Product::where('id', $id)->first();  // Global scope applies

// Bad - Bypassing tenant scope
Product::withoutGlobalScope(TenantScope::class)->find($id);  // Dangerous!
```

### 2. Authorization Policies

```php
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Product;

class ProductPolicy
{
    public function view(User $user, Product $product): bool
    {
        // Ensure user can only access products in their store
        return $user->store_id === $product->store_id;
    }

    public function update(User $user, Product $product): bool
    {
        return $user->store_id === $product->store_id
            && $user->can('products.update');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->store_id === $product->store_id
            && $user->can('products.delete');
    }
}
```

### 3. Input Validation

Never accept `store_id` from user input:

```php
// Bad - Allows tenant hopping
$product = Product::create($request->all());

// Good - Force current tenant
$product = Product::create([
    ...$request->validated(),
    'store_id' => app(TenantContext::class)->getCurrentStoreId(),
]);
```

### 4. Audit Logging

Log all cross-tenant access attempts:

```php
if ($user->store_id !== $resource->store_id) {
    Log::warning('Cross-tenant access attempt', [
        'user_id' => $user->id,
        'user_store' => $user->store_id,
        'resource_store' => $resource->store_id,
        'resource_type' => get_class($resource),
        'resource_id' => $resource->id,
    ]);
    abort(403, 'Unauthorized access');
}
```

## Store Onboarding Flow

### 1. Store Registration

```php
public function createStore(array $data): Store
{
    return DB::transaction(function () use ($data) {
        // Create store
        $store = Store::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'subdomain' => $data['subdomain'],
            'email' => $data['email'],
            'status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
        ]);

        // Create admin user
        $admin = User::create([
            'store_id' => $store->id,
            'name' => $data['admin_name'],
            'email' => $data['admin_email'],
            'password' => Hash::make($data['password']),
            'role' => 'admin',
        ]);

        // Create default settings
        $this->createDefaultSettings($store);

        // Create default categories
        $this->createDefaultCategories($store);

        // Send welcome email
        $admin->notify(new StoreCreatedNotification($store));

        return $store;
    });
}
```

### 2. Data Seeding

```php
private function createDefaultSettings(Store $store): void
{
    $defaultSettings = [
        ['key' => 'currency', 'value' => 'USD', 'type' => 'string'],
        ['key' => 'timezone', 'value' => 'UTC', 'type' => 'string'],
        ['key' => 'tax_rate', 'value' => '0', 'type' => 'integer'],
        ['key' => 'shipping_enabled', 'value' => 'true', 'type' => 'boolean'],
    ];

    foreach ($defaultSettings as $setting) {
        $store->settings()->create($setting);
    }
}
```

## Domain & Subdomain Management

### 1. Subdomain Routing

**Nginx Configuration**:

```nginx
server {
    listen 80;
    server_name *.yourplatform.com;
    
    location / {
        proxy_pass http://laravel-app:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

### 2. Custom Domain Support

**DNS Configuration Required**:
- Customer adds CNAME: `customdomain.com` → `stores.yourplatform.com`
- SSL certificate provisioning (Let's Encrypt via Laravel Vapor/Cloudflare)

**Domain Verification**:

```php
public function verifyCustomDomain(Store $store, string $domain): bool
{
    try {
        $records = dns_get_record($domain, DNS_CNAME);
        
        foreach ($records as $record) {
            if (str_contains($record['target'], 'yourplatform.com')) {
                $store->update(['domain' => $domain]);
                return true;
            }
        }
    } catch (\Exception $e) {
        Log::error('Domain verification failed', [
            'store_id' => $store->id,
            'domain' => $domain,
            'error' => $e->getMessage(),
        ]);
    }
    
    return false;
}
```

## Performance Considerations

### 1. Indexed Queries

Always include `store_id` in WHERE clauses:

```sql
-- Fast (uses composite index)
SELECT * FROM products
WHERE store_id = 1 AND status = 'active';

-- Slow (full table scan)
SELECT * FROM products
WHERE status = 'active';
```

### 2. Connection Pooling

Configure appropriate connection pool size:

```php
// config/database.php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'ecommerce'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'options' => [
        PDO::ATTR_PERSISTENT => true,  // Connection pooling
    ],
],
```

### 3. Query Optimization

Use eager loading to prevent N+1 queries:

```php
// Bad
$products = Product::all();
foreach ($products as $product) {
    echo $product->category->name;  // N+1 queries
}

// Good
$products = Product::with('category')->get();
foreach ($products as $product) {
    echo $product->category->name;  // Single query
}
```

## Migration to Multi-Database (Future)

If scaling demands require it, migrate to separate databases per tenant:

1. **Data Export**: Export each store's data
2. **Database Provisioning**: Create new database per store
3. **Data Import**: Import store data to isolated DB
4. **Update Tenant Resolver**: Change connection per tenant
5. **Gradual Migration**: Migrate stores in batches

## Testing Multi-Tenancy

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Store;
use App\Models\Product;
use App\Models\User;

class TenantIsolationTest extends TestCase
{
    public function test_users_can_only_access_their_store_products(): void
    {
        $store1 = Store::factory()->create();
        $store2 = Store::factory()->create();
        
        $user1 = User::factory()->create(['store_id' => $store1->id]);
        $product1 = Product::factory()->create(['store_id' => $store1->id]);
        $product2 = Product::factory()->create(['store_id' => $store2->id]);
        
        $this->actingAs($user1);
        
        // Can access own store's product
        $response = $this->getJson("/api/v1/admin/products/{$product1->id}");
        $response->assertStatus(200);
        
        // Cannot access other store's product
        $response = $this->getJson("/api/v1/admin/products/{$product2->id}");
        $response->assertStatus(404);
    }
}
```

## Monitoring & Alerting

Monitor for tenant isolation violations:

```php
// Log query patterns
DB::listen(function ($query) {
    if (!str_contains($query->sql, 'store_id') && 
        !in_array($query->connection->getTablePrefix(), ['migrations', 'jobs'])) {
        Log::warning('Query without store_id filter', [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
        ]);
    }
});
```

## Next Steps

1. Review [Scalability & Performance](08-scalability.md)
2. Review [Security Guidelines](09-security.md)
3. Review [Development Roadmap](10-development-roadmap.md)
