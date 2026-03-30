# Backend Architecture - Laravel

**Last Updated**: March 30, 2026  
**Status**: ✅ Phase 1 Foundation Complete (40%)

## Overview

The Laravel backend serves as the core API and business logic layer for the entire e-commerce platform. It provides RESTful APIs for both the admin panel and storefront applications.

## Current Implementation Status

### ✅ Completed (Phase 1 - 40%)

**Installed Packages**:
- Laravel 11.51.0 (Framework)
- Laravel Sanctum 4.3+ (API Authentication)
- Spatie Laravel Permission 6.25+ (Roles & Permissions)
- Spatie Query Builder 6.4+ (Advanced Query Filtering) 
- Knuckles Scribe 5.9+ (API Documentation)

**Implemented Structure**:
```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/V1/
│   │   │   └── AuthController.php          ✅ Login, logout, me, revoke tokens
│   │   └── Middleware/
│   │       └── SetTenantFromHeader.php     ✅ Tenant context validation
│   ├── Models/
│   │   ├── Concerns/
│   │   │   └── HasTenantScope.php          ✅ Global scope trait
│   │   ├── Store.php                        ✅ Store model with settings
│   │   ├── TenantModel.php                  ✅ Base tenant-aware model
│   │   └── User.php                         ✅ User with store relationships
│   └── helpers.php                          ✅ tenant(), tenant_id(), has_tenant()
├── database/
│   ├── factories/
│   │   └── StoreFactory.php                 ✅ Store test data generation
│   └── migrations/
│       ├── 2024_01_01_000001_create_stores_table.php              ✅
│       ├── 2024_01_01_000002_create_users_table.php               ✅
│       ├── 2024_01_01_000003_create_store_user_table.php          ✅
│       └── 2024_01_01_000004_create_personal_access_tokens_table.php ✅
├── routes/
│   └── api.php                              ✅ API routes configured
├── tests/
│   └── Feature/
│       └── TenantIsolationTest.php          ✅ Tenant security tests
└── bootstrap/
    └── app.php                              ✅ Middleware and routes registered
```

**API Endpoints Implemented**:
- `POST /api/v1/auth/login` - User authentication
- `POST /api/v1/auth/logout` - Token revocation
- `GET /api/v1/auth/me` - Get authenticated user (tenant-aware)
- `POST /api/v1/auth/revoke-all` - Revoke all user tokens

### ⏳ Planned (Phase 1 - Remaining 60%)

- Password reset flow
- Laravel Horizon queue monitoring
- Redis configuration
- Database seeders
- Additional tenant isolation tests
- Product model example (tenant-aware)

## Target Project Structure

```
backend/
├── app/
│   ├── Console/
│   │   └── Commands/          # Artisan commands
│   ├── Events/                # Domain events
│   ├── Exceptions/            # Custom exceptions
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/         # Admin API controllers
│   │   │   └── Storefront/    # Public API controllers
│   │   ├── Middleware/
│   │   ├── Requests/          # Form requests (validation)
│   │   └── Resources/         # API resources (transformers)
│   ├── Jobs/                  # Queue jobs
│   ├── Listeners/             # Event listeners
│   ├── Mail/                  # Email templates
│   ├── Models/                # Eloquent models
│   ├── Notifications/         # Notification classes
│   ├── Policies/              # Authorization policies
│   ├── Providers/             # Service providers
│   ├── Rules/                 # Custom validation rules
│   └── Services/              # Business logic services
│       ├── Tenant/
│       ├── Product/
│       ├── Inventory/
│       ├── Order/
│       ├── Promotion/
│       ├── Payment/
│       └── Analytics/
├── bootstrap/
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── resources/
│   └── views/                 # Email templates (Blade)
├── routes/
│   ├── api.php               # API routes
│   ├── web.php               # Web routes (minimal)
│   └── console.php           # Console routes
├── storage/
├── tests/
│   ├── Feature/
│   └── Unit/
├── .env.example
├── composer.json
└── README.md
```

## Multi-Tenancy Implementation (CRITICAL)

### ✅ Implemented Pattern (Phase 1)

**Strategy**: Single database with application-level isolation via `store_id` foreign keys.

**Core Components**:

#### 1. HasTenantScope Trait (app/Models/Concerns/HasTenantScope.php)

Automatically filters queries by current tenant:

```php
trait HasTenantScope
{
    protected static function bootHasTenantScope(): void
    {
        // Auto-filter queries by store_id
        static::addGlobalScope('store', function (Builder $builder) {
            if (tenant()->exists()) {
                $builder->where($builder->getQuery()->from . '.store_id', tenant()->id);
            }
        });

        // Auto-set store_id on create
        static::creating(function (Model $model) {
            if (tenant()->exists() && !$model->store_id) {
                $model->store_id = tenant()->id;
            }
        });
    }
}
```

#### 2. TenantModel Base Class (app/Models/TenantModel.php)

Base class for all tenant-aware models:

```php
abstract class TenantModel extends Model
{
    use HasTenantScope;

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
```

**Usage**:
```php
class Product extends TenantModel
{
    // Automatically scoped to current tenant
}
```

#### 3. SetTenantFromHeader Middleware (app/Http/Middleware/SetTenantFromHeader.php)

Validates tenant context from `X-Store-ID` header:

```php
public function handle(Request $request, Closure $next): Response
{
    $storeId = $request->header('X-Store-ID');

    if (!$storeId) {
        return response()->json(['message' => 'X-Store-ID header is required'], 400);
    }

    $store = Store::find($storeId);

    if (!$store || !$store->isActive()) {
        return response()->json(['message' => 'Store not found or inactive'], 403);
    }

    if (!$request->user()->hasAccessToStore($storeId)) {
        return response()->json(['message' => 'Access denied'], 403);
    }

    app()->instance('tenant', $store);
    return $next($request);
}
```

#### 4. Helper Functions (app/helpers.php)

Global tenant context accessors:

```php
function tenant(): ?\App\Models\Store
{
    return app()->has('tenant') ? app('tenant') : null;
}

function tenant_id(): ?int
{
    return tenant()?->id;
}

function has_tenant(): bool
{
    return tenant() !== null;
}
```

### Security Considerations (CRITICAL)

**✅ DO**:
- Always use `TenantModel` base class for multi-tenant entities
- Always require `X-Store-ID` header on tenant-scoped routes
- Test tenant isolation with `TenantIsolationTest`
- Use `tenant_id()` helper, never hardcode store IDs

**❌ DON'T**:
- Never manually add `where('store_id', ...)` - use global scopes
- Never trust client-provided store_id - validate via middleware
- Never disable global scope unless absolutely necessary (admin operations only)

### Testing Tenant Isolation

```php
// tests/Feature/TenantIsolationTest.php
public function test_products_are_scoped_to_current_store(): void
{
    $store1 = Store::factory()->create();
    $store2 = Store::factory()->create();
    
    $product1 = Product::factory()->create(['store_id' => $store1->id]);
    $product2 = Product::factory()->create(['store_id' => $store2->id]);
    
    app()->instance('tenant', $store1);
    $this->assertCount(1, Product::all()); // Only sees store1 product
}
```

## Core Modules

### 1. Tenant Management Module ✅ IMPLEMENTED

**Purpose**: Manage multiple stores with data isolation

**Implemented Components**:
- `App\Models\Store` ✅ - Store model with settings management
- `App\Models\User` ✅ - User with store relationships
- `App\Http\Middleware\SetTenantFromHeader` ✅ - Tenant resolution
- `App\Models\Concerns\HasTenantScope` ✅ - Global scope for tenant filtering
- Helper functions ✅ - tenant(), tenant_id(), has_tenant()

**Planned Components**:
- `App\Services\Tenant\TenantService` - Tenant operations
- `App\Services\Tenant\OnboardingService` - Store onboarding flow

**Implemented Features**:
- ✅ Store model with settings (JSON field)
- ✅ Store status management (active, inactive, suspended)
- ✅ User<->Store many-to-many with roles (owner, admin, manager, staff)
- ✅ Tenant context validation via X-Store-ID header
- ✅ Automatic tenant scoping for all queries

**Planned Features**:
- Store registration and onboarding
- Store domain/subdomain management
- Feature flag management per store
- Store subscription management

**Database Tables**:
- `stores` ✅ CREATED
- `store_user` ✅ CREATED (pivot)
- `store_settings` ⏳ (can use stores.settings JSON for now)
- `store_domains` ⏳

### 2. Product Catalog Module ⏳ PLANNED

**Purpose**: Manage products, categories, variants, and attributes

**Components**:
- `App\Models\Product` - Extends TenantModel
- `App\Models\Category`
- `App\Models\ProductVariant`
- `App\Models\ProductAttribute`
- `App\Services\Product\ProductService`
- `App\Services\Product\CategoryService`

**Key Features**:
- Product CRUD operations
- Category hierarchy management
- Product variants (size, color, etc.)
- Dynamic attributes
- Product images and media
- SEO metadata
- Bulk product import/export

**Database Tables**:
- `products`
- `categories`
- `product_variants`
- `product_attributes`
- `product_attribute_values`
- `product_images`
- `product_categories` (pivot)

### 3. Inventory Management Module ⏳ PLANNED

**Purpose**: Track and manage product stock levels

**Components**:
- `App\Models\Inventory`
- `App\Services\Inventory\InventoryService`
- `App\Services\Inventory\StockMovementService`
- `App\Jobs\UpdateInventoryJob`
- `App\Events\LowStockAlert`

**Key Features**:
- Real-time stock tracking
- Multi-warehouse support
- Stock movement history
- Low stock alerts
- Stock reservations during checkout
- Inventory adjustments (manual, damaged, returns)
- SKU management

**Database Tables**:
- `inventories`
- `warehouses`
- `stock_movements`
- `stock_reservations`

### 4. Promotion & Discount Engine

**Purpose**: Flexible promotion and discount system

**Components**:
- `App\Models\Promotion`
- `App\Models\Coupon`
- `App\Services\Promotion\PromotionEngine`
- `App\Services\Promotion\CouponService`
- `App\Services\Promotion\OfferService`
- `App\Rules\CouponValidator`

**Key Features**:
- Percentage discounts
- Fixed amount discounts
- Buy X Get Y offers
- Bundle offers
- Cart-level discounts
- Product-level discounts
- Coupon code management
- Time-based promotions
- Customer segment targeting
- Promotion stacking rules
- Usage limits and tracking

**Database Tables**:
- `promotions`
- `promotion_rules`
- `coupons`
- `coupon_usages`
- `offers`
- `offer_conditions`

### 5. Order Management Module

**Purpose**: Handle the complete order lifecycle

**Components**:
- `App\Models\Order`
- `App\Models\OrderItem`
- `App\Services\Order\OrderService`
- `App\Services\Order\CheckoutService`
- `App\Jobs\ProcessOrderJob`
- `App\StateMachines\OrderStateMachine`

**Key Features**:
- Order creation and validation
- Order status workflow
- Payment processing integration
- Order fulfillment
- Shipping management
- Order cancellation and refunds
- Invoice generation
- Order history and tracking

**Database Tables**:
- `orders`
- `order_items`
- `order_addresses`
- `order_status_histories`
- `shipments`
- `invoices`

**Order Status Flow**:
```
Pending → Confirmed → Processing → Shipped → Delivered
    ↓         ↓           ↓
Cancelled  Cancelled  Cancelled
              ↓
           Refunded
```

### 6. Customer Management Module

**Purpose**: Manage customer accounts and data

**Components**:
- `App\Models\Customer`
- `App\Models\CustomerAddress`
- `App\Services\Customer\CustomerService`

**Key Features**:
- Customer registration
- Profile management
- Address book
- Order history
- Wishlist
- Customer groups/segments
- Customer reviews and ratings

**Database Tables**:
- `customers`
- `customer_addresses`
- `customer_groups`
- `wishlists`
- `reviews`

### 7. Payment Module

**Purpose**: Abstract payment gateway integrations

**Components**:
- `App\Services\Payment\PaymentGatewayInterface`
- `App\Services\Payment\StripeGateway`
- `App\Services\Payment\PayPalGateway`
- `App\Models\Payment`
- `App\Models\Transaction`

**Key Features**:
- Multi-gateway support
- Payment processing
- Webhook handling
- Refund processing
- Payment method storage
- Transaction logging

**Database Tables**:
- `payments`
- `transactions`
- `payment_methods`

### 8. Analytics & Reporting Module

**Purpose**: Business intelligence and reporting

**Components**:
- `App\Services\Analytics\AnalyticsService`
- `App\Services\Analytics\ReportService`
- `App\Jobs\GenerateReportJob`

**Key Features**:
- Sales analytics
- Revenue reports
- Product performance
- Customer analytics
- Inventory reports
- Custom report builder
- Export to CSV/Excel

**Database Tables**:
- `analytics_events`
- `reports`
- `report_schedules`

## Laravel Package Architecture

### Core Packages

```json
{
  "require": {
    "php": "^8.2",
    "laravel/framework": "^11.0",
    "laravel/sanctum": "^4.0",
    "laravel/horizon": "^5.0",
    "laravel/telescope": "^5.0",
    "spatie/laravel-permission": "^6.0",
    "spatie/laravel-medialibrary": "^11.0",
    "spatie/laravel-query-builder": "^5.0",
    "spatie/laravel-backup": "^9.0",
    "league/flysystem-aws-s3-v3": "^3.0",
    "barryvdh/laravel-cors": "^3.0",
    "maatwebsite/excel": "^3.1"
  },
  "require-dev": {
    "laravel/pint": "^1.0",
    "phpunit/phpunit": "^10.0",
    "pestphp/pest": "^2.0",
    "fakerphp/faker": "^1.23"
  }
}
```

### Package Responsibilities

| Package | Purpose |
|---------|---------|
| **laravel/sanctum** | API authentication |
| **laravel/horizon** | Queue monitoring |
| **laravel/telescope** | Debugging and monitoring |
| **spatie/laravel-permission** | Role & permission management |
| **spatie/laravel-medialibrary** | File and media management |
| **spatie/laravel-query-builder** | API filtering, sorting, includes |
| **spatie/laravel-backup** | Database and file backups |
| **maatwebsite/excel** | Excel import/export |

## Service Layer Pattern

All business logic resides in service classes to keep controllers thin.

**Example: ProductService**

```php
<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Services\Tenant\TenantContext;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(
        private TenantContext $tenantContext,
        private InventoryService $inventoryService
    ) {}

    public function createProduct(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::create([
                'store_id' => $this->tenantContext->getCurrentStoreId(),
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'],
                'price' => $data['price'],
                'sku' => $data['sku'],
                'status' => $data['status'] ?? 'draft',
            ]);

            if (isset($data['categories'])) {
                $product->categories()->sync($data['categories']);
            }

            if (isset($data['attributes'])) {
                $this->attachAttributes($product, $data['attributes']);
            }

            if (isset($data['initial_stock'])) {
                $this->inventoryService->setStock(
                    $product->id,
                    $data['initial_stock']
                );
            }

            return $product->load(['categories', 'attributes', 'inventory']);
        });
    }

    public function updateProduct(Product $product, array $data): Product
    {
        DB::transaction(function () use ($product, $data) {
            $product->update($data);

            if (isset($data['categories'])) {
                $product->categories()->sync($data['categories']);
            }

            if (isset($data['attributes'])) {
                $this->updateAttributes($product, $data['attributes']);
            }
        });

        return $product->fresh();
    }

    private function attachAttributes(Product $product, array $attributes): void
    {
        foreach ($attributes as $attribute) {
            $product->attributeValues()->create([
                'attribute_id' => $attribute['attribute_id'],
                'value' => $attribute['value'],
            ]);
        }
    }
}
```

## API Structure

### RESTful Conventions

**Resource Naming**:
- Use plural nouns: `/api/v1/products`, `/api/v1/orders`
- Nested resources for relationships: `/api/v1/products/{id}/variants`
- Actions on resources: `/api/v1/orders/{id}/cancel`

**HTTP Methods**:
- GET - Retrieve resource(s)
- POST - Create resource
- PUT/PATCH - Update resource
- DELETE - Delete resource

**Response Format**:

```json
{
  "data": {
    "id": 1,
    "name": "Product Name",
    "price": 29.99
  },
  "meta": {
    "timestamp": "2026-03-30T10:00:00Z"
  }
}
```

**Pagination**:

```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 150,
    "last_page": 10
  },
  "links": {
    "first": "/api/v1/products?page=1",
    "last": "/api/v1/products?page=10",
    "next": "/api/v1/products?page=2",
    "prev": null
  }
}
```

### API Routes Structure

```php
// routes/api.php

Route::prefix('v1')->group(function () {
    
    // Public Storefront API
    Route::prefix('storefront')->group(function () {
        Route::get('products', [StorefrontProductController::class, 'index']);
        Route::get('products/{slug}', [StorefrontProductController::class, 'show']);
        Route::get('categories', [StorefrontCategoryController::class, 'index']);
        Route::post('cart', [CartController::class, 'store']);
        Route::post('checkout', [CheckoutController::class, 'process']);
    });

    // Admin API (Protected)
    Route::prefix('admin')->middleware(['auth:sanctum', 'tenant'])->group(function () {
        
        // Store Management
        Route::apiResource('stores', StoreController::class);
        Route::get('stores/{store}/settings', [StoreSettingsController::class, 'show']);
        Route::put('stores/{store}/settings', [StoreSettingsController::class, 'update']);
        
        // Products
        Route::apiResource('products', ProductController::class);
        Route::post('products/bulk-import', [ProductController::class, 'bulkImport']);
        Route::get('products/{product}/variants', [ProductVariantController::class, 'index']);
        
        // Categories
        Route::apiResource('categories', CategoryController::class);
        Route::get('categories/tree', [CategoryController::class, 'tree']);
        
        // Inventory
        Route::get('inventory', [InventoryController::class, 'index']);
        Route::post('inventory/{product}/adjust', [InventoryController::class, 'adjust']);
        Route::get('inventory/alerts', [InventoryController::class, 'lowStockAlerts']);
        
        // Promotions
        Route::apiResource('promotions', PromotionController::class);
        Route::post('promotions/{promotion}/activate', [PromotionController::class, 'activate']);
        Route::post('promotions/{promotion}/deactivate', [PromotionController::class, 'deactivate']);
        
        // Coupons
        Route::apiResource('coupons', CouponController::class);
        Route::get('coupons/{coupon}/usage', [CouponController::class, 'usage']);
        
        // Offers
        Route::apiResource('offers', OfferController::class);
        
        // Orders
        Route::apiResource('orders', OrderController::class);
        Route::post('orders/{order}/fulfill', [OrderController::class, 'fulfill']);
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel']);
        Route::post('orders/{order}/refund', [OrderController::class, 'refund']);
        
        // Customers
        Route::apiResource('customers', CustomerController::class);
        Route::get('customers/{customer}/orders', [CustomerController::class, 'orders']);
        
        // Analytics
        Route::get('analytics/sales', [AnalyticsController::class, 'sales']);
        Route::get('analytics/products', [AnalyticsController::class, 'productPerformance']);
        Route::get('analytics/customers', [AnalyticsController::class, 'customerInsights']);
        
        // Reports
        Route::get('reports', [ReportController::class, 'index']);
        Route::post('reports/generate', [ReportController::class, 'generate']);
        Route::get('reports/{report}/download', [ReportController::class, 'download']);
    });
});
```

## Middleware Stack

### Custom Middleware

1. **TenantContext** - Resolve and set current tenant
2. **ValidateStoreStatus** - Check if store is active
3. **RateLimitPerTenant** - Rate limiting per store
4. **ApiVersioning** - Handle API versioning
5. **LogApiRequests** - API request logging

## Event-Driven Architecture

### Key Events

```php
// Store Events
event(new StoreCreated($store));
event(new StoreStatusChanged($store, $oldStatus, $newStatus));

// Product Events
event(new ProductCreated($product));
event(new ProductUpdated($product));
event(new ProductDeleted($product));

// Inventory Events
event(new StockUpdated($product, $oldStock, $newStock));
event(new LowStockAlert($product, $currentStock));

// Order Events
event(new OrderPlaced($order));
event(new OrderStatusChanged($order, $oldStatus, $newStatus));
event(new OrderShipped($order, $shipment));

// Promotion Events
event(new PromotionApplied($order, $promotion));
event(new CouponUsed($coupon, $order));
```

### Event Listeners

```php
// Update inventory when order is placed
class UpdateInventoryOnOrderPlaced implements ShouldQueue
{
    public function handle(OrderPlaced $event): void
    {
        foreach ($event->order->items as $item) {
            $this->inventoryService->decrementStock(
                $item->product_id,
                $item->quantity
            );
        }
    }
}

// Send notification when order ships
class SendShipmentNotification implements ShouldQueue
{
    public function handle(OrderShipped $event): void
    {
        $event->order->customer->notify(
            new OrderShippedNotification($event->order, $event->shipment)
        );
    }
}
```

## Queue Jobs

### Background Jobs

```php
// Process order asynchronously
class ProcessOrderJob implements ShouldQueue
{
    public function __construct(
        private Order $order
    ) {}

    public function handle(
        OrderService $orderService,
        PaymentService $paymentService
    ): void {
        DB::transaction(function () use ($orderService, $paymentService) {
            // Process payment
            $payment = $paymentService->charge($this->order);
            
            // Update order status
            $orderService->confirmOrder($this->order, $payment);
            
            // Send confirmation email
            $this->order->customer->notify(
                new OrderConfirmedNotification($this->order)
            );
        });
    }
}
```

## Caching Strategy

### Cache Layers

1. **Query Result Caching**: Cache expensive database queries
2. **Model Caching**: Cache frequently accessed models
3. **API Response Caching**: Cache API responses for public endpoints
4. **View Caching**: Cache rendered views (email templates)

### Cache Tags

```php
Cache::tags(['products', "store:{$storeId}"])->put(
    "products:list:{$storeId}",
    $products,
    now()->addHours(1)
);

// Invalidate on product update
Cache::tags(['products', "store:{$storeId}"])->flush();
```

## Testing Strategy

### Test Structure
```
tests/
├── Unit/
│   ├── Services/
│   ├── Models/
│   └── Rules/
└── Feature/
    ├── Api/
    │   ├── Admin/
    │   └── Storefront/
    └── Jobs/
```

### Test Example

```php
<?php

namespace Tests\Feature\Api\Admin;

use Tests\TestCase;
use App\Models\Store;
use App\Models\User;
use App\Models\Product;

class ProductApiTest extends TestCase
{
    public function test_admin_can_create_product(): void
    {
        $store = Store::factory()->create();
        $admin = User::factory()->admin()->create(['store_id' => $store->id]);

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/admin/products', [
                'name' => 'Test Product',
                'slug' => 'test-product',
                'price' => 29.99,
                'sku' => 'TEST-001',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'slug', 'price', 'sku']
            ]);

        $this->assertDatabaseHas('products', [
            'store_id' => $store->id,
            'name' => 'Test Product',
            'sku' => 'TEST-001',
        ]);
    }
}
```

## Performance Optimization

### Database Optimization
- Proper indexing on foreign keys and search columns
- Query optimization using Laravel Query Builder
- Eager loading to prevent N+1 queries
- Database connection pooling

### Caching
- Redis for session and cache
- Query result caching
- API response caching
- Fragment caching

### Queue Management
- Async processing for heavy operations
- Job prioritization
- Job batching
- Failed job handling

## Security Measures

- Input validation using Form Requests
- SQL injection prevention (Eloquent ORM)
- XSS protection
- CSRF protection
- Rate limiting
- Authentication via Sanctum
- Authorization via Laravel Policies
- API token encryption
- Secure password storage (bcrypt)

## Next Steps

1. Review [Database Schema](03-database-schema.md)
2. Review [API Design](04-api-design.md)
3. Set up Laravel development environment
