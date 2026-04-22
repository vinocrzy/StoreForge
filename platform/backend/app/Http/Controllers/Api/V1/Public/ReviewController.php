<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * @group Product Reviews (Storefront)
 *
 * Public endpoints for browsing and submitting product reviews.
 */
class ReviewController extends Controller
{
    public function __construct(
        private ReviewService $reviewService
    ) {}

    /**
     * List product reviews
     *
     * Retrieve paginated approved reviews and rating summary for a product.
     * No authentication required.
     *
     * @urlParam slug string required Product slug. Example: honey-lavender-soap
     * @queryParam per_page integer Items per page (max 50). Example: 10
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "rating": 5,
     *       "title": "Amazing product",
     *       "body": "This soap is wonderful...",
     *       "is_verified_purchase": true,
     *       "customer_name": "Jane D.",
     *       "created_at": "2026-04-20T10:00:00.000000Z"
     *     }
     *   ],
     *   "summary": {
     *     "avg_rating": 4.2,
     *     "review_count": 15,
     *     "distribution": {"5": 8, "4": 4, "3": 2, "2": 1, "1": 0}
     *   },
     *   "meta": {"current_page": 1, "per_page": 10, "total": 15}
     * }
     * @response 404 scenario="Product not found" {
     *   "message": "Product not found"
     * }
     */
    public function index(Request $request, string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)
            ->where('status', 'active')
            ->first();

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $perPage = min((int) $request->input('per_page', 10), 50);
        $reviews = $this->reviewService->getProductReviews($product->id, $perPage);
        $summary = $this->reviewService->getProductRatingSummary($product->id);

        return response()->json([
            'data' => $reviews->items(),
            'summary' => $summary,
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
                'last_page' => $reviews->lastPage(),
            ],
        ]);
    }

    /**
     * Submit a product review
     *
     * Submit a review for a product. Customer must be authenticated.
     * Only one review per product per customer is allowed.
     * Verified purchase status is auto-detected from delivered orders.
     *
     * @authenticated
     *
     * @urlParam slug string required Product slug. Example: honey-lavender-soap
     * @bodyParam rating integer required Rating from 1 to 5. Example: 5
     * @bodyParam title string optional Review title (max 100 characters). Example: Amazing product
     * @bodyParam body string required Review body (20-2000 characters). Example: This soap is absolutely wonderful. The scent is lovely and it leaves my skin feeling so soft.
     *
     * @response 201 scenario="Created" {
     *   "message": "Review submitted successfully. It will be visible after approval.",
     *   "data": {
     *     "id": 1,
     *     "product_id": 42,
     *     "rating": 5,
     *     "title": "Amazing product",
     *     "body": "This soap is absolutely wonderful...",
     *     "status": "pending",
     *     "is_verified_purchase": true,
     *     "created_at": "2026-04-22T10:00:00.000000Z"
     *   }
     * }
     * @response 422 scenario="Already reviewed" {
     *   "message": "You have already reviewed this product.",
     *   "errors": {"product_id": ["You have already reviewed this product."]}
     * }
     * @response 404 scenario="Product not found" {
     *   "message": "Product not found"
     * }
     */
    public function store(Request $request, string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)
            ->where('status', 'active')
            ->first();

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:100',
            'body' => 'required|string|min:20|max:2000',
        ]);

        $review = $this->reviewService->submit(
            $request->user()->id,
            $product->id,
            $validated
        );

        return response()->json([
            'message' => 'Review submitted successfully. It will be visible after approval.',
            'data' => $review,
        ], 201);
    }
}
