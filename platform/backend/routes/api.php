<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\CategoryController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/v1/auth/login', [AuthController::class, 'login']);
Route::post('/v1/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/v1/auth/reset-password', [AuthController::class, 'resetPassword']);

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
});
