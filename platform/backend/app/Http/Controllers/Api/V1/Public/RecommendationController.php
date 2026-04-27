<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Services\RecommendationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @group Product Recommendations
 *
 * Endpoints for rule-based product recommendations.
 */
class RecommendationController extends Controller
{
    public function __construct(private readonly RecommendationService $service) {}

    /**
     * Get product recommendations
     *
     * Returns similar products or frequently-bought-together products.
     *
     * @urlParam product_id integer required The product ID. Example: 1
     * @queryParam type string Recommendation type: similar or bought_together. Default: similar. Example: bought_together
     * @queryParam limit integer Max results. Default: 6. Example: 4
     *
     * @response 200 {"data": [{"id": 2, "name": "Product B", ...}]}
     */
    public function forProduct(Request $request, int $productId): JsonResponse
    {
        $type = $request->input('type', 'similar');
        $limit = min((int) $request->input('limit', 6), 12);
        $storeId = tenant()->id;

        $products = match ($type) {
            'bought_together' => $this->service->getBoughtTogether($productId, $storeId, $limit),
            default => $this->service->getSimilarProducts($productId, $storeId, $limit),
        };

        return response()->json(['data' => $products->values()]);
    }

    /**
     * Get cart-based recommendations
     *
     * Returns products frequently bought alongside items in the given cart.
     *
     * @bodyParam product_ids integer[] required Array of product IDs in cart. Example: [1, 2]
     * @queryParam limit integer Max results. Default: 4. Example: 4
     *
     * @response 200 {"data": [...]}
     */
    public function forCart(Request $request): JsonResponse
    {
        $request->validate([
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'integer',
        ]);

        $limit = min((int) $request->input('limit', 4), 8);
        $storeId = tenant()->id;

        $products = $this->service->getCartRecommendations(
            $request->input('product_ids'),
            $storeId,
            $limit
        );

        return response()->json(['data' => $products->values()]);
    }
}
