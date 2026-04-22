<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class WishlistService
{
    /**
     * Toggle a product in the customer's wishlist.
     *
     * Adds the product if it is not already wishlisted; removes it if it is.
     *
     * @return bool True if added, false if removed.
     */
    public function toggle(int $customerId, int $productId): bool
    {
        $existing = Wishlist::where('customer_id', $customerId)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            $existing->delete();
            return false;
        }

        Wishlist::create([
            'customer_id' => $customerId,
            'product_id'  => $productId,
        ]);

        return true;
    }

    /**
     * Get paginated wishlist products for a customer.
     */
    public function getCustomerWishlist(int $customerId, int $perPage = 15): LengthAwarePaginator
    {
        return Wishlist::where('customer_id', $customerId)
            ->with(['product.images', 'product.categories'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Check if a single product is wishlisted by a customer.
     */
    public function isWishlisted(int $customerId, int $productId): bool
    {
        return Wishlist::where('customer_id', $customerId)
            ->where('product_id', $productId)
            ->exists();
    }

    /**
     * Batch-check multiple products against a customer's wishlist.
     *
     * @param  int   $customerId
     * @param  int[] $productIds
     * @return array<int, bool> Associative array keyed by product ID.
     */
    public function checkMultiple(int $customerId, array $productIds): array
    {
        $wishlisted = Wishlist::where('customer_id', $customerId)
            ->whereIn('product_id', $productIds)
            ->pluck('product_id')
            ->all();

        $result = [];
        foreach ($productIds as $id) {
            $result[$id] = in_array($id, $wishlisted, true);
        }

        return $result;
    }

    /**
     * Get the most-wishlisted products for the current store.
     */
    public function getMostWishlisted(int $limit = 10): Collection
    {
        return Wishlist::select('product_id')
            ->selectRaw('COUNT(*) as wishlist_count')
            ->groupBy('product_id')
            ->orderByDesc('wishlist_count')
            ->limit($limit)
            ->with('product')
            ->get();
    }

    /**
     * Explicitly remove a product from a customer's wishlist.
     *
     * @return bool True if a record was deleted, false if nothing matched.
     */
    public function remove(int $customerId, int $productId): bool
    {
        $deleted = Wishlist::where('customer_id', $customerId)
            ->where('product_id', $productId)
            ->delete();

        return $deleted > 0;
    }
}
