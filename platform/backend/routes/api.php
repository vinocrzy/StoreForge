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
Route::post('/v1/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/v1/auth/reset-password', [AuthController::class, 'resetPassword']);

// Protected routes (require authentication + tenant scope)
Route::middleware(['auth:sanctum', 'tenant'])->prefix('v1')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/revoke-all', [AuthController::class, 'revokeAllTokens']);
    
    // Product routes will be added here
    // Route::apiResource('products', ProductController::class);
});
