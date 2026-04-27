<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProductImageController;
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
use App\Http\Controllers\Api\V1\Public\WishlistController;
use App\Http\Controllers\Api\V1\Public\ReviewController;
use App\Http\Controllers\Api\V1\Public\PaymentController as PublicPaymentController;
use App\Http\Controllers\Api\V1\Admin\WishlistReportController;
use App\Http\Controllers\Api\V1\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Api\V1\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Api\V1\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Api\V1\Public\CouponController as PublicCouponController;
use App\Http\Controllers\Api\V1\Webhook\StripeWebhookController;
use App\Http\Controllers\Api\V1\Webhook\RazorpayWebhookController;
// Phase 9B
use App\Http\Controllers\Api\V1\Admin\AbandonedCartController;
use App\Http\Controllers\Api\V1\Admin\TaxSettingsController;
use App\Http\Controllers\Api\V1\Admin\ShippingMethodController;
use App\Http\Controllers\Api\V1\Admin\OrderTrackingController;
use App\Http\Controllers\Api\V1\Admin\AnalyticsController;
use App\Http\Controllers\Api\V1\Admin\ReturnController as AdminReturnController;
use App\Http\Controllers\Api\V1\Public\ShippingController;
use App\Http\Controllers\Api\V1\Storefront\ReturnController as StorefrontReturnController;
// Phase 9C
use App\Http\Controllers\Api\V1\Public\RecommendationController;
use App\Http\Controllers\Api\V1\Public\NewsletterController;
use App\Http\Controllers\Api\V1\Admin\LoyaltyController as AdminLoyaltyController;
use App\Http\Controllers\Api\V1\Admin\StoreSettingsExtController;
use App\Http\Controllers\Api\V1\Storefront\LoyaltyController as StorefrontLoyaltyController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/v1/auth/login', [AuthController::class, 'login']);
Route::post('/v1/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/v1/auth/reset-password', [AuthController::class, 'resetPassword']);

// -------------------------------------------------------------------------
// Webhook routes (no auth, no CSRF, no tenant middleware)
// Store is resolved from payment metadata in the webhook payload.
// -------------------------------------------------------------------------
Route::prefix('v1/webhooks')->group(function () {
    Route::post('/stripe', [StripeWebhookController::class, 'handle']);
    Route::post('/razorpay', [RazorpayWebhookController::class, 'handle']);
});

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
    
    // Product Images
    Route::post('/products/{id}/images', [ProductImageController::class, 'store']);
    Route::delete('/products/{id}/images/{imageId}', [ProductImageController::class, 'destroy']);
    Route::patch('/products/{id}/images/{imageId}/primary', [ProductImageController::class, 'setPrimary']);
    
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

    // Reports
    Route::get('/reports/most-wishlisted', [WishlistReportController::class, 'mostWishlisted']);

    // Reviews (admin)
    Route::get('/reviews', [AdminReviewController::class, 'index']);
    Route::get('/reviews/{id}', [AdminReviewController::class, 'show']);
    Route::patch('/reviews/{id}', [AdminReviewController::class, 'update']);
    Route::delete('/reviews/{id}', [AdminReviewController::class, 'destroy']);

    // Payments (admin)
    Route::get('/payments', [AdminPaymentController::class, 'index']);
    Route::post('/orders/{id}/refund', [AdminPaymentController::class, 'refund']);

    // Coupons (admin CRUD)
    Route::apiResource('coupons', AdminCouponController::class);

    // Phase 9B: Abandoned Cart Recovery
    Route::get('/admin/abandoned-carts', [AbandonedCartController::class, 'index']);
    Route::get('/admin/analytics/abandoned-carts', [AbandonedCartController::class, 'analytics']);

    // Phase 9B: Tax Settings
    Route::get('/admin/settings/tax', [TaxSettingsController::class, 'show']);
    Route::put('/admin/settings/tax', [TaxSettingsController::class, 'update']);

    // Phase 9B: Shipping Methods (admin CRUD)
    Route::apiResource('admin/shipping-methods', ShippingMethodController::class);

    // Phase 9B: Order Tracking
    Route::patch('/admin/orders/{id}/tracking', [OrderTrackingController::class, 'update']);

    // Phase 9B: Analytics Dashboard
    Route::get('/admin/analytics/dashboard', [AnalyticsController::class, 'dashboard']);
    Route::get('/admin/analytics/revenue', [AnalyticsController::class, 'revenue']);
    Route::get('/admin/analytics/top-products', [AnalyticsController::class, 'topProducts']);
    Route::get('/admin/analytics/customers', [AnalyticsController::class, 'customers']);

    // Phase 9B: Returns & Refunds (admin)
    Route::get('/admin/returns', [AdminReturnController::class, 'index']);
    Route::get('/admin/returns/{id}', [AdminReturnController::class, 'show']);
    Route::patch('/admin/returns/{id}/approve', [AdminReturnController::class, 'approve']);
    Route::patch('/admin/returns/{id}/reject', [AdminReturnController::class, 'reject']);
    Route::post('/admin/returns/{id}/refund', [AdminReturnController::class, 'processRefund']);

    // Phase 9C: Loyalty Program (admin)
    Route::get('/admin/loyalty/config', [AdminLoyaltyController::class, 'getConfig']);
    Route::put('/admin/loyalty/config', [AdminLoyaltyController::class, 'updateConfig']);
    Route::get('/admin/loyalty/customers/{customerId}', [AdminLoyaltyController::class, 'customerPoints']);
    Route::post('/admin/loyalty/customers/{customerId}/adjust', [AdminLoyaltyController::class, 'adjustPoints']);

    // Phase 9C: Currency & Email Marketing Settings (admin)
    Route::get('/admin/settings/currency', [StoreSettingsExtController::class, 'getCurrencySettings']);
    Route::put('/admin/settings/currency', [StoreSettingsExtController::class, 'updateCurrencySettings']);
    Route::get('/admin/settings/email-marketing', [StoreSettingsExtController::class, 'getEmailMarketingSettings']);
    Route::put('/admin/settings/email-marketing', [StoreSettingsExtController::class, 'updateEmailMarketingSettings']);

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

    // Product reviews (public, no auth)
    Route::get('/products/{slug}/reviews', [ReviewController::class, 'index']);

    // Coupon validation (storefront, no auth required)
    Route::post('/coupons/validate', [PublicCouponController::class, 'validate']);

    // Phase 9B: Public shipping methods
    Route::get('/shipping-methods', [ShippingController::class, 'index']);

    // Phase 9C: Product Recommendations (public, no auth)
    Route::get('/products/{productId}/recommendations', [RecommendationController::class, 'forProduct']);
    Route::post('/recommendations/cart', [RecommendationController::class, 'forCart']);

    // Phase 9C: Newsletter subscribe/unsubscribe (public)
    Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe']);
    Route::post('/newsletter/unsubscribe', [NewsletterController::class, 'unsubscribe']);

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

    // Payment gateway config (public — returns gateway type only, no secrets)
    Route::get('/payment-config', function () {
        $storeId = tenant()->id;
        $gateway = \App\Models\StoreSetting::where('store_id', $storeId)
            ->where('key', 'payment_gateway')
            ->value('value') ?? 'manual';

        return response()->json(['payment_gateway' => $gateway]);
    });

    // Customer account (requires customer Sanctum token)
    Route::middleware(['auth:sanctum', 'ensure_customer'])->group(function () {
        Route::post('/customer/logout', [CustomerAuthController::class, 'logout']);
        Route::get('/customer/profile', [CustomerAccountController::class, 'profile']);
        Route::patch('/customer/profile', [CustomerAccountController::class, 'updateProfile']);
        Route::get('/customer/orders', [CustomerAccountController::class, 'orders']);
        Route::get('/customer/orders/{id}', [CustomerAccountController::class, 'orderDetail']);

        // Product reviews (authenticated customer)
        Route::post('/products/{slug}/reviews', [ReviewController::class, 'store']);

        // Payment intent (customer checkout)
        Route::post('/checkout/payment-intent', [PublicPaymentController::class, 'createIntent']);

        // Wishlist
        Route::get('/wishlist', [WishlistController::class, 'index']);
        Route::post('/wishlist', [WishlistController::class, 'toggle']);
        Route::delete('/wishlist/{productId}', [WishlistController::class, 'remove']);
        Route::get('/wishlist/check/{productId}', [WishlistController::class, 'check']);
        Route::post('/wishlist/check', [WishlistController::class, 'checkMultiple']);

        // Phase 9B: Customer returns
        Route::get('/returns', [StorefrontReturnController::class, 'index']);
        Route::post('/returns', [StorefrontReturnController::class, 'store']);
        Route::get('/returns/{id}', [StorefrontReturnController::class, 'show']);

        // Phase 9C: Customer loyalty
        Route::get('/loyalty/balance', [StorefrontLoyaltyController::class, 'balance']);
        Route::get('/loyalty/history', [StorefrontLoyaltyController::class, 'history']);
        Route::post('/loyalty/validate-redemption', [StorefrontLoyaltyController::class, 'validateRedemption']);
    });
});
