<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\WishlistService;
use Illuminate\Http\JsonResponse;

/**
 * @group Reports
 *
 * Admin reporting endpoints.
 *
 * @authenticated
 */
class WishlistReportController extends Controller
{
    public function __construct(
        private WishlistService $wishlistService
    ) {}

    /**
     * Most wishlisted products
     *
     * Returns the top 10 most-wishlisted products for the current store.
     *
     * @queryParam limit integer Number of products to return (max 50). Example: 10
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "product_id": 42,
     *       "wishlist_count": 128,
     *       "product": {"id": 42, "name": "Premium Laptop", "price": "999.99"}
     *     }
     *   ]
     * }
     */
    public function mostWishlisted(): JsonResponse
    {
        $limit = min((int) request()->input('limit', 10), 50);

        $products = $this->wishlistService->getMostWishlisted($limit);

        return response()->json(['data' => $products]);
    }
}
