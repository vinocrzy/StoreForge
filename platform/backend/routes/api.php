<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\WarehouseController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\StockAlertController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\StoreController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\SettingsController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\Public\PublicProductController;
use App\Http\Controllers\Api\V1\Public\CartController;
use App\Http\Controllers\Api\V1\Public\CustomerAuthController;
use App\Http\Controllers\Api\V1\Public\CustomerAccountController;
use App\Http\Controllers\Api\V1\Public\CheckoutController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/v1/auth/login', [AuthController::class, 'login']);
Route::post('/v1/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/v1/auth/reset-password', [AuthController::class, 'resetPassword']);

// Super admin global routes (no tenant header required)
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('/stores', [StoreController::class, 'index']);
    Route::post('/stores', [StoreController::class, 'store']);
    Route::get('/stores/{id}', [StoreController::class, 'show']);
    Route::patch('/stores/{id}/status', [StoreController::class, 'updateStatus']);
});

// Protected routes (require authentication + tenant scope)
Route::middleware(['auth:sanctum', 'tenant'])->prefix('v1')->group(function () {
    // Authentication
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/revoke-all', [AuthController::class, 'revokeAllTokens']);
    
    // Dashboard
    Route::get('/dashboard/statistics', [DashboardController::class, 'statistics']);
    Route::get('/dashboard/recent-orders', [DashboardController::class, 'recentOrders']);
    Route::get('/dashboard/sales-chart', [DashboardController::class, 'salesChart']);
    Route::get('/dashboard/top-products', [DashboardController::class, 'topProducts']);
    Route::get('/dashboard/activity-log', [DashboardController::class, 'activityLog']);
    
    // Products
    Route::get('/products/export', [ProductController::class, 'export']);
    Route::post('/products/bulk-action', [ProductController::class, 'bulkAction']);
    Route::apiResource('products', ProductController::class);
    Route::post('/products/{id}/stock', [ProductController::class, 'updateStock']);
    
    // Categories
    Route::get('/categories/tree', [CategoryController::class, 'tree']);
    Route::post('/categories/reorder', [CategoryController::class, 'reorder']);
    Route::post('/categories/{id}/move', [CategoryController::class, 'move']);
    Route::apiResource('categories', CategoryController::class);
    
    // Customers
    Route::get('/customers/export', [CustomerController::class, 'export']);
    Route::post('/customers/bulk-action', [CustomerController::class, 'bulkAction']);
    Route::get('/customers/statistics', [CustomerController::class, 'statistics']);
    Route::post('/customers/{id}/status', [CustomerController::class, 'updateStatus']);
    Route::post('/customers/{id}/verify-email', [CustomerController::class, 'verifyEmail']);
    Route::post('/customers/{id}/verify-phone', [CustomerController::class, 'verifyPhone']);
    Route::get('/customers/{customerId}/addresses', [CustomerController::class, 'listAddresses']);
    Route::post('/customers/{customerId}/addresses', [CustomerController::class, 'storeAddress']);
    Route::get('/customers/{customerId}/addresses/{addressId}', [CustomerController::class, 'showAddress']);
    Route::put('/customers/{customerId}/addresses/{addressId}', [CustomerController::class, 'updateAddress']);
    Route::delete('/customers/{customerId}/addresses/{addressId}', [CustomerController::class, 'destroyAddress']);
    Route::post('/customers/{customerId}/addresses/{addressId}/default', [CustomerController::class, 'setDefaultAddress']);
    Route::apiResource('customers', CustomerController::class);
    
    // Warehouses
    Route::apiResource('warehouses', WarehouseController::class);
    Route::patch('/warehouses/{id}/set-default', [WarehouseController::class, 'setDefault']);
    
    // Inventory
    Route::get('/inventory/export', [InventoryController::class, 'export']);
    Route::get('/inventory/movements', [InventoryController::class, 'movements']);
    Route::get('/inventory/product/{productId}', [InventoryController::class, 'byProduct']);
    Route::post('/inventory/adjust', [InventoryController::class, 'adjust']);
    Route::post('/inventory/reserve', [InventoryController::class, 'reserve']);
    Route::post('/inventory/release', [InventoryController::class, 'release']);
    Route::post('/inventory/fulfill', [InventoryController::class, 'fulfill']);
    Route::post('/inventory/transfer', [InventoryController::class, 'transfer']);
    Route::apiResource('inventory', InventoryController::class)->only(['index', 'show', 'store']);

    // Stock alerts
    Route::get('/stock-alerts', [StockAlertController::class, 'index']);
    Route::patch('/stock-alerts/{id}/resolve', [StockAlertController::class, 'resolve']);

    // Store Settings
    Route::get('/settings', [SettingsController::class, 'index']);
    Route::get('/settings/{group}', [SettingsController::class, 'show']);
    Route::patch('/settings', [SettingsController::class, 'update']);

    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::patch('/profile/password', [ProfileController::class, 'changePassword']);

    // Orders
    Route::get('/orders/export', [OrderController::class, 'export']);
    Route::get('/orders/statistics', [OrderController::class, 'statistics']);
    Route::post('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
    Route::post('/orders/{order}/payment', [OrderController::class, 'recordPayment']);
    Route::post('/orders/{order}/fulfill', [OrderController::class, 'fulfill']);
    Route::apiResource('orders', OrderController::class);
});

// -------------------------------------------------------------------------
// Public Storefront Routes (no admin auth — X-Store-ID header sets tenant)
// -------------------------------------------------------------------------
Route::middleware(['public_tenant'])->prefix('v1/public')->group(function () {

    // Product browsing
    Route::get('/products', [PublicProductController::class, 'index']);
    Route::get('/products/{slug}', [PublicProductController::class, 'show']);
    Route::get('/featured-products', [PublicProductController::class, 'featured']);
    Route::get('/categories', [PublicProductController::class, 'categories']);
    Route::get('/categories/{slug}', [PublicProductController::class, 'showCategory']);

    // Cart (token-based, no auth required)
    Route::post('/cart', [CartController::class, 'create']);
    Route::get('/cart/{token}', [CartController::class, 'show']);
    Route::post('/cart/{token}/items', [CartController::class, 'addItem']);
    Route::patch('/cart/{token}/items/{itemId}', [CartController::class, 'updateItem']);
    Route::delete('/cart/{token}/items/{itemId}', [CartController::class, 'removeItem']);
    Route::delete('/cart/{token}', [CartController::class, 'clear']);

    // Customer auth
    Route::post('/customer/register', [CustomerAuthController::class, 'register']);
    Route::post('/customer/login', [CustomerAuthController::class, 'login']);

    // Checkout (guest or authenticated)
    Route::post('/checkout', [CheckoutController::class, 'process']);

    // Customer account (requires customer Sanctum token)
    Route::middleware(['auth:sanctum', 'ensure_customer'])->group(function () {
        Route::post('/customer/logout', [CustomerAuthController::class, 'logout']);
        Route::get('/customer/profile', [CustomerAccountController::class, 'profile']);
        Route::patch('/customer/profile', [CustomerAccountController::class, 'updateProfile']);
        Route::get('/customer/orders', [CustomerAccountController::class, 'orders']);
        Route::get('/customer/orders/{id}', [CustomerAccountController::class, 'orderDetail']);
    });
});
