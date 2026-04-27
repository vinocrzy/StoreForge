<?php

namespace App\Services;

use App\Models\Product;
use App\Models\OrderItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class RecommendationService
{
    /**
     * Get products from the same category as the given product.
     */
    public function getSimilarProducts(int $productId, int $storeId, int $limit = 6): Collection
    {
        $cacheKey = "recommendations:similar:{$storeId}:{$productId}";

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($productId, $storeId, $limit) {
            // Get the product's category
            $product = Product::withoutGlobalScope('store')
                ->where('store_id', $storeId)
                ->where('id', $productId)
                ->first(['id', 'category_id']);

            if (!$product || !$product->category_id) {
                return collect();
            }

            return Product::withoutGlobalScope('store')
                ->where('store_id', $storeId)
                ->where('id', '!=', $productId)
                ->where('category_id', $product->category_id)
                ->where('status', 'active')
                ->with(['primaryImage', 'category'])
                ->withAvg(['reviews as avg_rating' => fn ($q) => $q->where('status', 'approved')], 'rating')
                ->orderBy('avg_rating', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get products frequently bought together with the given product.
     * Computed from co-occurrence in orders.
     */
    public function getBoughtTogether(int $productId, int $storeId, int $limit = 4): Collection
    {
        $cacheKey = "recommendations:bought_together:{$storeId}:{$productId}";

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($productId, $storeId, $limit) {
            // Find orders that contain this product
            $orderIds = DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.store_id', $storeId)
                ->where('order_items.product_id', $productId)
                ->whereNotNull('orders.id')
                ->pluck('order_items.order_id');

            if ($orderIds->isEmpty()) {
                return $this->getSimilarProducts($productId, $storeId, $limit);
            }

            // Find other products in those orders
            $coProducts = DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->join('products', 'products.id', '=', 'order_items.product_id')
                ->where('orders.store_id', $storeId)
                ->where('products.status', 'active')
                ->whereIn('order_items.order_id', $orderIds)
                ->where('order_items.product_id', '!=', $productId)
                ->select('order_items.product_id', DB::raw('COUNT(*) as co_count'))
                ->groupBy('order_items.product_id')
                ->orderByDesc('co_count')
                ->limit($limit)
                ->pluck('product_id');

            if ($coProducts->isEmpty()) {
                return $this->getSimilarProducts($productId, $storeId, $limit);
            }

            return Product::withoutGlobalScope('store')
                ->where('store_id', $storeId)
                ->whereIn('id', $coProducts)
                ->where('status', 'active')
                ->with(['primaryImage', 'category'])
                ->get()
                ->sortBy(fn ($p) => array_search($p->id, $coProducts->values()->toArray()))
                ->values();
        });
    }

    /**
     * Get recommendations based on cart contents.
     */
    public function getCartRecommendations(array $productIds, int $storeId, int $limit = 4): Collection
    {
        $cacheKey = "recommendations:cart:{$storeId}:" . md5(implode(',', sort($productIds) ? $productIds : $productIds));

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($productIds, $storeId, $limit) {
            $orderIds = DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.store_id', $storeId)
                ->whereIn('order_items.product_id', $productIds)
                ->pluck('order_items.order_id')
                ->unique();

            if ($orderIds->isEmpty()) {
                return collect();
            }

            $coProducts = DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->join('products', 'products.id', '=', 'order_items.product_id')
                ->where('orders.store_id', $storeId)
                ->where('products.status', 'active')
                ->whereIn('order_items.order_id', $orderIds)
                ->whereNotIn('order_items.product_id', $productIds)
                ->select('order_items.product_id', DB::raw('COUNT(*) as co_count'))
                ->groupBy('order_items.product_id')
                ->orderByDesc('co_count')
                ->limit($limit)
                ->pluck('product_id');

            if ($coProducts->isEmpty()) {
                return collect();
            }

            return Product::withoutGlobalScope('store')
                ->where('store_id', $storeId)
                ->whereIn('id', $coProducts)
                ->where('status', 'active')
                ->with(['primaryImage', 'category'])
                ->get();
        });
    }
}
