<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Coupons (Public)
 *
 * Storefront coupon validation endpoint. Allows customers to check if a
 * coupon code is valid and see the discount before checkout.
 */
class CouponController extends Controller
{
    public function __construct(
        private CouponService $couponService
    ) {}

    /**
     * Validate coupon code
     *
     * Check whether a coupon code is valid for the given cart subtotal.
     * Returns the discount amount if valid, or an error message if not.
     *
     * @bodyParam code string required The coupon code to validate. Example: SUMMER20
     * @bodyParam cart_subtotal number required The cart subtotal amount. Example: 89.99
     *
     * @response 200 scenario="Valid coupon" {
     *   "valid": true,
     *   "discount": 17.99,
     *   "message": "Coupon applied successfully"
     * }
     * @response 200 scenario="Invalid coupon" {
     *   "valid": false,
     *   "discount": 0,
     *   "message": "Invalid coupon code"
     * }
     * @response 422 scenario="Validation error" {
     *   "message": "The given data was invalid.",
     *   "errors": {"code": ["The code field is required."]}
     * }
     */
    public function validate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'cart_subtotal' => 'required|numeric|min:0',
        ]);

        $customerId = $request->user()?->id;

        $result = $this->couponService->validate(
            $validated['code'],
            (float) $validated['cart_subtotal'],
            $customerId
        );

        return response()->json([
            'valid' => $result['valid'],
            'discount' => $result['discount'],
            'message' => $result['valid'] ? 'Coupon applied successfully' : $result['error'],
        ]);
    }
}
