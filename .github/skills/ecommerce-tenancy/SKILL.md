---
name: ecommerce-tenancy
description: 'Implement and verify multi-tenant features with proper data isolation. Use when: creating models that need tenant scoping, implementing API controllers, adding database migrations for tenant data, testing tenant isolation, or debugging data leakage issues.'
argument-hint: 'Specify "model", "controller", "test", or "review" for tenant isolation check'
---

# Multi-Tenant E-Commerce Implementation

## Purpose

Ensure proper multi-tenant implementation with complete data isolation across stores. Every store's data must be isolated from other stores using application-level scoping.

## When to Use

- Creating new models that store tenant-specific data
- Implementing API controllers for tenant resources
- Adding database migrations for multi-tenant tables
- Writing tests to verify tenant isolation
- Reviewing existing code for tenant data leakage
- Debugging cross-tenant data access issues

## Critical Principles

### 1. Single Database Architecture
- One database serves all stores
- Application-level isolation via `store_id`
- Global scopes enforce tenant filtering automatically
- Never query without tenant context

### 2. Security First
- **NEVER** trust `store_id` from client requests
- Always get tenant context from authenticated user
- Test tenant isolation for every new feature
- Cross-tenant data access is a CRITICAL security bug

## Implementation Patterns

### Pattern 1: Tenant-Aware Model

**When to use**: Any model that stores per-store data (products, orders, customers, etc.)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'name',
        'slug',
        'sku',
        'price',
        'status',
        // ... other fields
    ];

    /**
     * Boot model and apply global scope for tenant isolation
     */
    protected static function booted()
    {
        // CRITICAL: Automatically filter all queries by current store
        static::addGlobalScope('store', function (Builder $builder) {
            if (tenant()->exists()) {
                $builder->where('store_id', tenant()->id);
            }
        });

        // CRITICAL: Automatically set store_id when creating
        static::creating(function ($model) {
            if (!$model->store_id && tenant()->exists()) {
                $model->store_id = tenant()->id;
            }
        });
    }

    /**
     * Relationship to store
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Query builder without tenant scope (use with EXTREME caution)
     * Only for admin operations across all stores
     */
    public static function withoutTenancy()
    {
        return static::withoutGlobalScope('store');
    }
}
```

### Pattern 2: Tenant-Aware Controller

**When to use**: API controllers that work with tenant data

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Http\Requests\ProductRequest;
use Illuminate\Http\Request;

/**
 * @group Products
 * 
 * Manage products for the authenticated store
 * 
 * @header X-Store-ID required The store identifier
 */
class ProductController extends Controller
{
    /**
     * List products
     * 
     * Get products for the current store. Automatically scoped by tenant.
     * 
     * @authenticated
     * 
     * @queryParam search string Search products. Example: laptop
     * @queryParam status string Filter: active, draft. Example: active
     * 
     * @response 200 {"data": [...], "meta": {...}}
     */
    public function index(Request $request)
    {
        $request->validate([
            'search' => 'string|max:255',
            'status' => 'in:active,draft,archived',
        ]);

        // ✅ GOOD: Global scope automatically applies tenant filter
        $query = Product::query();

        // Apply additional filters
        if ($request->has('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Tenant isolation happens automatically
        return $query->paginate(20);
    }

    /**
     * Get product details
     * 
     * @urlParam id integer required Product ID. Example: 1
     * 
     * @response 200 {"data": {...}}
     * @response 404 {"message": "Product not found"}
     */
    public function show(int $id)
    {
        // ✅ GOOD: findOrFail respects global scope
        // Will return 404 if product doesn't belong to current store
        $product = Product::with(['images', 'categories'])->findOrFail($id);

        return response()->json(['data' => $product]);
    }

    /**
     * Create product
     * 
     * @bodyParam name string required Product name. Example: Laptop
     * @bodyParam sku string required SKU. Example: LAP-001
     * @bodyParam price number required Price. Example: 999.99
     * 
     * @response 201 {"data": {...}}
     */
    public function store(ProductRequest $request)
    {
        // ✅ GOOD: store_id automatically set in model's creating event
        $product = Product::create($request->validated());

        return response()->json(['data' => $product], 201);
    }

    /**
     * Update product
     * 
     * @urlParam id integer required Product ID. Example: 1
     * @bodyParam name string Product name. Example: Updated Name
     * 
     * @response 200 {"data": {...}}
     */
    public function update(ProductRequest $request, int $id)
    {
        // ✅ GOOD: findOrFail respects tenant scope
        $product = Product::findOrFail($id);
        $product->update($request->validated());

        return response()->json(['data' => $product]);
    }

    /**
     * Delete product
     * 
     * @urlParam id integer required Product ID. Example: 1
     * 
     * @response 204
     */
    public function destroy(int $id)
    {
        // ✅ GOOD: Automatically scoped to current store
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->noContent();
    }
}
```

### Pattern 3: Tenant Middleware

**When to use**: Validate and set tenant context on every API request

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Store;

class SetTenantFromHeader
{
    /**
     * Handle incoming request and set tenant context
     */
    public function handle(Request $request, Closure $next)
    {
        // Get store ID from header
        $storeId = $request->header('X-Store-ID');

        if (!$storeId) {
            return response()->json([
                'message' => 'X-Store-ID header is required'
            ], 400);
        }

        // Verify user has access to this store
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Check if user has access to requested store
        $store = $user->stores()->find($storeId);

        if (!$store) {
            return response()->json([
                'message' => 'You do not have access to this store'
            ], 403);
        }

        // Set global tenant context
        app()->instance('tenant', $store);

        return $next($request);
    }
}
```

**Register in `app/Http/Kernel.php`:**
```php
protected $middlewareGroups = [
    'api' => [
        \App\Http\Middleware\SetTenantFromHeader::class,
        // ... other middleware
    ],
];
```

### Pattern 4: Tenant Helper

**When to use**: Access current tenant anywhere in the application

```php
<?php

// app/helpers.php

if (!function_exists('tenant')) {
    /**
     * Get current tenant (store)
     */
    function tenant(): ?\App\Models\Store
    {
        return app('tenant') ?? null;
    }
}
```

**Usage:**
```php
// Get current store ID
$storeId = tenant()->id;

// Check if tenant is set
if (tenant()->exists()) {
    // ...
}

// Get tenant name
$storeName = tenant()->name;
```

### Pattern 5: Migration for Tenant Table

**When to use**: Creating database tables that need tenant isolation

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            
            // CRITICAL: store_id for tenant isolation
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();
            
            $table->string('name');
            $table->string('slug');
            $table->string('sku')->unique();
            $table->decimal('price', 10, 2);
            $table->enum('status', ['active', 'draft', 'archived'])
                ->default('draft');
            
            $table->timestamps();
            $table->softDeletes();
            
            // CRITICAL: Composite indexes with store_id first
            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'created_at']);
            $table->unique(['store_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

### Pattern 6: Tenant Isolation Test

**When to use**: Testing that data doesn't leak between tenants

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Store;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test products are scoped to tenant
     */
    public function test_products_are_scoped_to_current_store()
    {
        // Create two stores
        $store1 = Store::factory()->create(['name' => 'Store 1']);
        $store2 = Store::factory()->create(['name' => 'Store 2']);

        // Create users for each store
        $user1 = User::factory()->create();
        $user1->stores()->attach($store1);
        
        $user2 = User::factory()->create();
        $user2->stores()->attach($store2);

        // Create products for each store
        $product1 = Product::factory()->create([
            'store_id' => $store1->id,
            'name' => 'Store 1 Product'
        ]);
        
        $product2 = Product::factory()->create([
            'store_id' => $store2->id,
            'name' => 'Store 2 Product'
        ]);

        // Test store 1 user can only see store 1 products
        $response = $this->actingAs($user1)
            ->withHeader('X-Store-ID', $store1->id)
            ->getJson('/api/v1/products');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $product1->id)
            ->assertJsonPath('data.0.name', 'Store 1 Product');

        // Test store 2 user can only see store 2 products
        $response = $this->actingAs($user2)
            ->withHeader('X-Store-ID', $store2->id)
            ->getJson('/api/v1/products');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $product2->id)
            ->assertJsonPath('data.0.name', 'Store 2 Product');
    }

    /**
     * Test user cannot access products from unauthorized store
     */
    public function test_user_cannot_access_unauthorized_store_products()
    {
        $store1 = Store::factory()->create();
        $store2 = Store::factory()->create();

        $user = User::factory()->create();
        $user->stores()->attach($store1); // User only has access to store1

        $product = Product::factory()->create(['store_id' => $store2->id]);

        // Try to access store2 product with store1 user
        $response = $this->actingAs($user)
            ->withHeader('X-Store-ID', $store2->id)
            ->getJson("/api/v1/products/{$product->id}");

        // Should get 403 Forbidden
        $response->assertForbidden();
    }

    /**
     * Test creating product automatically sets store_id
     */
    public function test_creating_product_sets_store_id_automatically()
    {
        $store = Store::factory()->create();
        $user = User::factory()->create();
        $user->stores()->attach($store);

        $response = $this->actingAs($user)
            ->withHeader('X-Store-ID', $store->id)
            ->postJson('/api/v1/products', [
                'name' => 'New Product',
                'sku' => 'TEST-001',
                'price' => 99.99,
                // Note: NOT sending store_id
            ]);

        $response->assertCreated();
        
        // Verify store_id was set automatically
        $this->assertDatabaseHas('products', [
            'name' => 'New Product',
            'store_id' => $store->id,
        ]);
    }
}
```

## Common Mistakes to Avoid

### ❌ Bad: Manual store_id filtering

```php
// DON'T DO THIS - Error prone and inconsistent
$products = Product::where('store_id', $storeId)->get();
```

### ✅ Good: Use global scope

```php
// DO THIS - Global scope handles it automatically
$products = Product::all();
```

### ❌ Bad: Trusting client-provided store_id

```php
// DON'T DO THIS - Security vulnerability
public function store(Request $request)
{
    $product = Product::create([
        'store_id' => $request->store_id, // NEVER trust client input
        'name' => $request->name,
    ]);
}
```

### ✅ Good: Use tenant context

```php
// DO THIS - Get from authenticated tenant
public function store(Request $request)
{
    $product = Product::create([
        'name' => $request->name,
        // store_id set automatically in model's creating event
    ]);
}
```

### ❌ Bad: Bypassing tenant scope without reason

```php
// DON'T DO THIS - Bypasses tenant isolation
$allProducts = Product::withoutGlobalScope('store')->get();
```

### ✅ Good: Only bypass for legitimate admin operations

```php
// DO THIS - Only for platform admin viewing all stores
if ($user->isPlatformAdmin()) {
    $allProducts = Product::withoutGlobalScope('store')->get();
}
```

## Payment Handling (Current Implementation)

### Manual Payment Processing

**Current Phase**: Orders use manual payment processing - no automated payment gateways yet.

**Implementation Pattern**:
```php
// Order Controller - Mark as Paid
public function markAsPaid(Request $request, int $id)
{
    // ✅ Tenant-scoped query (automatic via global scope)
    $order = Order::findOrFail($id);
    
    $request->validate([
        'payment_method' => 'required|string|max:100',
        'payment_notes' => 'nullable|string',
        'amount' => 'required|numeric|min:0',
    ]);
    
    // Verify amount matches order total
    if ($request->amount != $order->total) {
        return response()->json([
            'message' => 'Payment amount must match order total'
        ], 422);
    }
    
    $order->update([
        'payment_status' => 'paid',
        'payment_method' => $request->payment_method,
        'paid_at' => now(),
        'paid_by_user_id' => auth()->id(),
        'payment_notes' => $request->payment_notes,
    ]);
    
    // Create payment record
    $order->payments()->create([
        'store_id' => tenant()->id,  // ✅ Tenant isolation
        'gateway' => 'manual',
        'payment_method' => $request->payment_method,
        'amount' => $request->amount,
        'currency' => $order->currency,
        'status' => 'completed',
        'processed_at' => now(),
    ]);
    
    // Send payment confirmation email
    event(new OrderPaid($order));
    
    return response()->json(['data' => $order]);
}
```

**Future**: Payment gateway integration (Stripe, PayPal, etc.) will be added in Phase 3+. See [docs/17-payment-strategy.md](../../docs/17-payment-strategy.md) for migration path. The manual payment system will remain available alongside automated gateways.

## Phone-First Authentication

### CRITICAL: Phone Numbers Are Required

**All users and customers MUST have phone numbers**:
- Phone number is the **primary** authentication method
- Email is **secondary** (required but used for communications)
- Phone numbers must be in E.164 format: `+12025551234`
- Phone numbers must be unique per store (tenant isolation)

**Login Implementation**:
```php
public function login(Request $request)
{
    $request->validate([
        'login' => 'required|string',  // Accept phone or email
        'password' => 'required|string',
    ]);
    
    // Detect if login is phone or email
    $loginField = preg_match('/^[\d\s\-\+\(\)]+$/', $request->login) 
        ? 'phone' 
        : 'email';
    
    $user = User::where($loginField, $request->login)->first();
    
    if (!$user || !Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages([
            'login' => ['The provided credentials are incorrect.'],
        ]);
    }
    
    // Track login method
    $user->update([
        'last_login_at' => now(),
        'last_login_method' => $loginField,
    ]);
    
    return response()->json([
        'user' => $user,
        'token' => $user->createToken('device')->plainTextToken,
    ]);
}
```

**Checkout Validation** - Phone Required:
```php
// CRITICAL: Phone number MANDATORY for all checkouts
$request->validate([
    'customer.phone' => 'required|string|max:20',
    'customer.email' => 'required|email|max:255',
    'shipping_address.phone' => 'required|string|max:20',  // For delivery
    'billing_address.phone' => 'required|string|max:20',
    // ... other fields
]);
```

**See**: [docs/18-phone-authentication-strategy.md](../../docs/18-phone-authentication-strategy.md) for complete phone authentication implementation.

## Reference Documentation

- [docs/07-multi-tenancy.md](../../docs/07-multi-tenancy.md) - Complete multi-tenancy strategy
- [docs/01-system-architecture.md](../../docs/01-system-architecture.md) - System architecture
- [docs/03-database-schema.md](../../docs/03-database-schema.md) - Database schema with tenant tables
- [docs/17-payment-strategy.md](../../docs/17-payment-strategy.md) - Payment implementation strategy
- [docs/18-phone-authentication-strategy.md](../../docs/18-phone-authentication-strategy.md) - Phone-first authentication

## Checklist for New Tenant Features

- [ ] Model has `store_id` field
- [ ] Migration includes `store_id` foreign key
- [ ] Composite indexes include `store_id` first
- [ ] Model has global scope for tenant filtering
- [ ] Model auto-sets `store_id` on create
- [ ] Controller uses global scope (no manual filtering)
- [ ] Middleware validates tenant access
- [ ] Tests verify tenant isolation
- [ ] Tests verify no cross-tenant access
- [ ] API documentation includes X-Store-ID header

## Testing Commands

```bash
# Run tenant isolation tests
php artisan test --filter=TenantIsolation

# Run all feature tests (includes tenant tests)
php artisan test --filter=Feature

# Run specific test
php artisan test --filter=test_products_are_scoped_to_current_store
```

---

**CRITICAL**: Tenant isolation is a security requirement. Every feature that handles tenant data MUST be tested for proper isolation!
