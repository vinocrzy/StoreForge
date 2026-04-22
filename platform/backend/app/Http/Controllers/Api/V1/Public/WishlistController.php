<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Wishlist
 *
 * Manage the authenticated customer's product wishlist.
 *
 * @authenticated
 */
class WishlistController extends Controller
{
    public function __construct(
        private WishlistService $wishlistService
    ) {}

    /**
     * Get customer wishlist
     *
     * Returns a paginated list of the authenticated customer's wishlisted products
     * including product images and categories.
     *
     * @queryParam per_page integer Items per page (max 50). Example: 15
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "product_id": 42,
     *       "created_at": "2026-04-22T10:00:00.000000Z",
     *       "product": {"id": 42, "name": "Premium Laptop", "price": "999.99", "slug": "premium-laptop"}
     *     }
     *   ],
     *   "meta": {"current_page": 1, "per_page": 15, "total": 5}
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 15), 50);

        $wishlist = $this->wishlistService->getCustomerWishlist(
            $request->user()->id,
            $perPage
        );

        return response()->json($wishlist);
    }

    /**
     * Toggle wishlist item
     *
     * Adds a product to the wishlist if it is not already there, or removes it
     * if it is. Returns the resulting state.
     *
     * @bodyParam product_id integer required The product to toggle. Example: 42
     *
     * @response 200 scenario="Added" {
     *   "message": "Product added to wishlist",
     *   "wishlisted": true
     * }
     * @response 200 scenario="Removed" {
     *   "message": "Product removed from wishlist",
     *   "wishlisted": false
     * }
     * @response 422 scenario="Validation error" {
     *   "message": "The product id field is required.",
     *   "errors": {"product_id": ["The product id field is required."]}
     * }
     */
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $added = $this->wishlistService->toggle(
            $request->user()->id,
            $request->input('product_id')
        );

        return response()->json([
            'message'    => $added ? 'Product added to wishlist' : 'Product removed from wishlist',
            'wishlisted' => $added,
        ]);
    }

    /**
     * Remove wishlist item
     *
     * Explicitly remove a product from the customer's wishlist.
     *
     * @urlParam productId integer required The product ID to remove. Example: 42
     *
     * @response 200 scenario="Removed" {
     *   "message": "Product removed from wishlist"
     * }
     * @response 404 scenario="Not found" {
     *   "message": "Product not in wishlist"
     * }
     */
    public function remove(int $productId): JsonResponse
    {
        $removed = $this->wishlistService->remove(
            request()->user()->id,
            $productId
        );

        if (!$removed) {
            return response()->json(['message' => 'Product not in wishlist'], 404);
        }

        return response()->json(['message' => 'Product removed from wishlist']);
    }

    /**
     * Check single product
     *
     * Check whether a single product is in the authenticated customer's wishlist.
     *
     * @urlParam productId integer required The product ID to check. Example: 42
     *
     * @response 200 scenario="Success" {
     *   "wishlisted": true
     * }
     */
    public function check(int $productId): JsonResponse
    {
        $wishlisted = $this->wishlistService->isWishlisted(
            request()->user()->id,
            $productId
        );

        return response()->json(['wishlisted' => $wishlisted]);
    }

    /**
     * Batch check products
     *
     * Check multiple products at once to see which ones are in the customer's
     * wishlist. Useful for product listing pages.
     *
     * @bodyParam product_ids integer[] required Array of product IDs to check. Example: [1, 2, 3]
     *
     * @response 200 scenario="Success" {
     *   "data": {"1": true, "2": false, "3": true}
     * }
     * @response 422 scenario="Validation error" {
     *   "message": "The product ids field is required.",
     *   "errors": {"product_ids": ["The product ids field is required."]}
     * }
     */
    public function checkMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'product_ids'   => 'required|array|max:100',
            'product_ids.*' => 'integer',
        ]);

        $result = $this->wishlistService->checkMultiple(
            $request->user()->id,
            $request->input('product_ids')
        );

        return response()->json(['data' => $result]);
    }
}
