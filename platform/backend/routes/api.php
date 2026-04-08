<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\WarehouseController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\StoreController;

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
    
    // Products
    Route::apiResource('products', ProductController::class);
    Route::post('/products/{id}/stock', [ProductController::class, 'updateStock']);
    
    // Categories
    Route::get('/categories/tree', [CategoryController::class, 'tree']);
    Route::post('/categories/reorder', [CategoryController::class, 'reorder']);
    Route::post('/categories/{id}/move', [CategoryController::class, 'move']);
    Route::apiResource('categories', CategoryController::class);
    
    // Customers
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
    
    // Inventory
    Route::get('/inventory/movements', [InventoryController::class, 'movements']);
    Route::get('/inventory/product/{productId}', [InventoryController::class, 'byProduct']);
    Route::post('/inventory/adjust', [InventoryController::class, 'adjust']);
    Route::post('/inventory/reserve', [InventoryController::class, 'reserve']);
    Route::post('/inventory/release', [InventoryController::class, 'release']);
    Route::post('/inventory/fulfill', [InventoryController::class, 'fulfill']);
    Route::post('/inventory/transfer', [InventoryController::class, 'transfer']);
    Route::apiResource('inventory', InventoryController::class)->only(['index', 'show', 'store']);
    
    // Orders
    Route::get('/orders/statistics', [OrderController::class, 'statistics']);
    Route::post('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
    Route::post('/orders/{order}/payment', [OrderController::class, 'recordPayment']);
    Route::post('/orders/{order}/fulfill', [OrderController::class, 'fulfill']);
    Route::apiResource('orders', OrderController::class);
});
