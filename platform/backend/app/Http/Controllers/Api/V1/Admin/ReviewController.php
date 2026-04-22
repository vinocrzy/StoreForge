<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Reviews (Admin)
 *
 * Admin endpoints for managing product reviews. Approve, reject, respond to,
 * and delete reviews.
 *
 * @authenticated
 */
class ReviewController extends Controller
{
    public function __construct(
        private ReviewService $reviewService
    ) {}

    /**
     * List all reviews
     *
     * Retrieve paginated reviews with optional status and product filters.
     *
     * @queryParam status string Filter by status: pending, approved, rejected. Example: pending
     * @queryParam product_id integer Filter by product. Example: 42
     * @queryParam per_page integer Items per page (max 50). Example: 15
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "rating": 5,
     *       "title": "Amazing product",
     *       "body": "This soap is wonderful...",
     *       "status": "pending",
     *       "is_verified_purchase": true,
     *       "customer": {"id": 1, "first_name": "Jane", "last_name": "Doe"},
     *       "product": {"id": 42, "name": "Honey Lavender Soap", "slug": "honey-lavender-soap"},
     *       "created_at": "2026-04-22T10:00:00.000000Z"
     *     }
     *   ],
     *   "meta": {"current_page": 1, "per_page": 15, "total": 30}
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'product_id']);
        $perPage = min((int) $request->input('per_page', 15), 50);

        $reviews = $this->reviewService->getAllReviews($filters, $perPage);

        return response()->json($reviews);
    }

    /**
     * Get review details
     *
     * Retrieve a single review with customer, product, and order details.
     *
     * @urlParam id integer required Review ID. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": 1,
     *     "rating": 5,
     *     "title": "Amazing product",
     *     "body": "This soap is wonderful...",
     *     "status": "pending",
     *     "is_verified_purchase": true,
     *     "admin_response": null,
     *     "customer": {"id": 1, "first_name": "Jane", "last_name": "Doe"},
     *     "product": {"id": 42, "name": "Honey Lavender Soap"},
     *     "order": {"id": 10, "order_number": "ORD-1-260420-1234"}
     *   }
     * }
     * @response 404 scenario="Not found" {
     *   "message": "Review not found"
     * }
     */
    public function show(int $id): JsonResponse
    {
        $review = \App\Models\ProductReview::with([
            'customer:id,first_name,last_name,email,phone',
            'product:id,name,slug',
            'order:id,order_number,status',
        ])->find($id);

        if (!$review) {
            return response()->json(['message' => 'Review not found'], 404);
        }

        return response()->json(['data' => $review]);
    }

    /**
     * Update review (approve/reject/respond)
     *
     * Update review status and/or add an admin response.
     *
     * @urlParam id integer required Review ID. Example: 1
     * @bodyParam status string Status update: approved or rejected. Example: approved
     * @bodyParam rejection_reason string Reason for rejection (required when status=rejected). Example: Contains inappropriate content
     * @bodyParam admin_response string Admin's public response to the review. Example: Thank you for your feedback!
     *
     * @response 200 scenario="Approved" {
     *   "message": "Review approved",
     *   "data": {"id": 1, "status": "approved"}
     * }
     * @response 200 scenario="Rejected" {
     *   "message": "Review rejected",
     *   "data": {"id": 1, "status": "rejected", "rejection_reason": "Contains inappropriate content"}
     * }
     * @response 404 scenario="Not found" {
     *   "message": "Review not found"
     * }
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'sometimes|in:approved,rejected',
            'rejection_reason' => 'nullable|string|max:255',
            'admin_response' => 'nullable|string|max:2000',
        ]);

        $review = \App\Models\ProductReview::find($id);

        if (!$review) {
            return response()->json(['message' => 'Review not found'], 404);
        }

        $message = 'Review updated';

        if ($request->has('status')) {
            if ($request->input('status') === 'approved') {
                $review = $this->reviewService->approve($id);
                $message = 'Review approved';
            } else {
                $review = $this->reviewService->reject($id, $request->input('rejection_reason'));
                $message = 'Review rejected';
            }
        }

        if ($request->filled('admin_response')) {
            $review = $this->reviewService->addAdminResponse($id, $request->input('admin_response'));
        }

        return response()->json([
            'message' => $message,
            'data' => $review,
        ]);
    }

    /**
     * Delete review
     *
     * Soft-delete a review. The review can be restored later if needed.
     *
     * @urlParam id integer required Review ID. Example: 1
     *
     * @response 200 scenario="Deleted" {
     *   "message": "Review deleted"
     * }
     * @response 404 scenario="Not found" {
     *   "message": "Review not found"
     * }
     */
    public function destroy(int $id): JsonResponse
    {
        $review = \App\Models\ProductReview::find($id);

        if (!$review) {
            return response()->json(['message' => 'Review not found'], 404);
        }

        // Update cached rating before deleting
        $productId = $review->product_id;
        $review->delete();
        $this->reviewService->updateProductRatingCache($productId);

        return response()->json(['message' => 'Review deleted']);
    }
}
