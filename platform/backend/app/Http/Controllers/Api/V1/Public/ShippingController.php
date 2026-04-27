<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Services\ShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Shipping Methods (Public)
 *
 * Public endpoint to get available shipping methods and their calculated rates.
 */
class ShippingController extends Controller
{
    public function __construct(private ShippingService $shippingService) {}

    /**
     * List available shipping methods
     *
     * Returns active shipping methods with rates calculated against the provided cart total.
     *
     * @queryParam cart_total number Required. Cart subtotal used to determine free-shipping thresholds. Example: 75.00
     * @queryParam cart_weight number Total weight in kg for weight-based methods. Example: 1.5
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1, "name": "Standard Shipping", "type": "flat_rate",
     *       "calculated_rate": 5.99, "is_active": true
     *     },
     *     {
     *       "id": 2, "name": "Free Shipping over $100", "type": "free_above",
     *       "free_above": "100.00", "calculated_rate": 0.0, "is_active": true
     *     }
     *   ]
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $cartTotal  = (float) $request->input('cart_total', 0);
        $cartWeight = (float) $request->input('cart_weight', 0);
        $storeId    = tenant()->id;

        $methods = $this->shippingService->getAvailableMethods($cartTotal, $storeId)
            ->map(function ($method) use ($cartTotal, $cartWeight) {
                $method->calculated_rate = $this->shippingService->calculateRate($method, $cartTotal, $cartWeight);
                return $method;
            });

        return response()->json(['data' => $methods->values()]);
    }
}
