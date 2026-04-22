<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewService
{
    /**
     * Submit a new review for a product.
     *
     * @throws ValidationException if customer already reviewed this product.
     */
    public function submit(int $customerId, int $productId, array $data): ProductReview
    {
        // Check if customer already reviewed this product
        $exists = ProductReview::where('customer_id', $customerId)
            ->where('product_id', $productId)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'product_id' => ['You have already reviewed this product.'],
            ]);
        }

        // Auto-detect verified purchase: customer has a delivered order containing this product
        $verifiedOrderId = Order::where('customer_id', $customerId)
            ->where('status', 'delivered')
            ->whereHas('items', fn ($q) => $q->where('product_id', $productId))
            ->value('id');

        return ProductReview::create([
            'customer_id' => $customerId,
            'product_id' => $productId,
            'order_id' => $verifiedOrderId,
            'rating' => $data['rating'],
            'title' => $data['title'] ?? null,
            'body' => $data['body'],
            'status' => 'pending',
            'is_verified_purchase' => (bool) $verifiedOrderId,
        ]);
    }

    /**
     * Get paginated approved reviews for a product (public).
     * Includes customer first name + last initial.
     */
    public function getProductReviews(int $productId, int $perPage = 10): LengthAwarePaginator
    {
        return ProductReview::where('product_id', $productId)
            ->approved()
            ->with(['customer:id,first_name,last_name'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->through(function (ProductReview $review) {
                $customer = $review->customer;
                $review->unsetRelation('customer');
                $review->setAttribute('customer_name', $customer
                    ? $customer->first_name . ' ' . mb_substr($customer->last_name, 0, 1) . '.'
                    : 'Anonymous'
                );
                return $review;
            });
    }

    /**
     * Get rating summary for a product.
     *
     * @return array{avg_rating: float|null, review_count: int, distribution: array<int, int>}
     */
    public function getProductRatingSummary(int $productId): array
    {
        $stats = ProductReview::where('product_id', $productId)
            ->approved()
            ->selectRaw('COUNT(*) as review_count, AVG(rating) as avg_rating')
            ->first();

        $distribution = ProductReview::where('product_id', $productId)
            ->approved()
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->all();

        // Ensure all 5 ratings are present
        $fullDistribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $fullDistribution[$i] = $distribution[$i] ?? 0;
        }

        return [
            'avg_rating' => $stats->review_count > 0 ? round((float) $stats->avg_rating, 1) : null,
            'review_count' => (int) $stats->review_count,
            'distribution' => $fullDistribution,
        ];
    }

    /**
     * Approve a review and update product rating cache.
     */
    public function approve(int $reviewId): ProductReview
    {
        $review = ProductReview::findOrFail($reviewId);
        $review->update(['status' => 'approved']);

        $this->updateProductRatingCache($review->product_id);

        return $review->fresh();
    }

    /**
     * Reject a review with optional reason.
     */
    public function reject(int $reviewId, ?string $reason = null): ProductReview
    {
        $review = ProductReview::findOrFail($reviewId);
        $review->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        $this->updateProductRatingCache($review->product_id);

        return $review->fresh();
    }

    /**
     * Add admin response to a review.
     */
    public function addAdminResponse(int $reviewId, string $response): ProductReview
    {
        $review = ProductReview::findOrFail($reviewId);
        $review->update([
            'admin_response' => $response,
            'admin_responded_at' => now(),
        ]);

        return $review->fresh();
    }

    /**
     * Recalculate avg_rating and review_count from approved reviews, save to product.
     */
    public function updateProductRatingCache(int $productId): void
    {
        $stats = ProductReview::where('product_id', $productId)
            ->approved()
            ->selectRaw('COUNT(*) as review_count, AVG(rating) as avg_rating')
            ->first();

        Product::withoutGlobalScope('store')
            ->where('id', $productId)
            ->update([
                'avg_rating' => $stats->review_count > 0 ? round((float) $stats->avg_rating, 1) : null,
                'review_count' => (int) $stats->review_count,
            ]);
    }

    /**
     * Get all reviews for admin, filterable by status and product_id.
     */
    public function getAllReviews(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = ProductReview::with(['customer:id,first_name,last_name', 'product:id,name,slug'])
            ->orderBy('created_at', 'desc');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        return $query->paginate($perPage);
    }
}
