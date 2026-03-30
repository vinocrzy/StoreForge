<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/v1/auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/revoke-all', [AuthController::class, 'revokeAllTokens']);
    
    // Tenant-scoped routes
    Route::middleware(['tenant'])->group(function () {
        // Product routes will be added here
        // Route::apiResource('products', ProductController::class);
    });
});
