<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Public Storefront
 *
 * Convert a shopping cart into an order. Supports both guest and authenticated checkout.
 */
class CheckoutController extends Controller
{
    public function __construct(
        private CartService $cartService,
        private CheckoutService $checkoutService,
    ) {}

    /**
     * Process checkout
     *
     * Convert the cart identified by `cart_token` into a confirmed order.
     * For guest checkout, `email`, `first_name`, `last_name`, and `phone` are required.
     * For authenticated customers, these fields are pulled from the customer's account.
     *
     * @bodyParam cart_token string required Cart token. Example: abc123xyz
     * @bodyParam payment_method string required Payment method: cod, bank_transfer, card, pending. Example: cod
     * @bodyParam email string required (guest only) Guest email address. Example: jane@example.com
     * @bodyParam first_name string required (guest only) Guest first name. Example: Jane
     * @bodyParam last_name string required (guest only) Guest last name. Example: Doe
     * @bodyParam phone string required (guest only) Guest phone number. Example: +12025551234
     * @bodyParam note string Optional order note. Example: Please leave at the door
     *
     * @response 201 scenario="Order created" {
     *   "data": {"id": 42, "order_number": "ORD-ABC12345", "status": "pending", "total": "29.98"},
     *   "order_number": "ORD-ABC12345",
     *   "message": "Order placed successfully."
     * }
     * @response 404 scenario="Cart not found" {"message": "Cart not found or expired."}
     * @response 422 scenario="Empty cart" {"message": "Cart is empty."}
     */
    public function process(Request $request): JsonResponse
    {
        $isAuthenticated = $request->user() instanceof Customer;

        $rules = [
            'cart_token'     => 'required|string',
            'payment_method' => 'required|string|in:cod,bank_transfer,card,pending',
            'note'           => 'nullable|string|max:500',
        ];

        // Guest checkout requires contact info
        if (!$isAuthenticated) {
            $rules['email']      = 'required|email|max:255';
            $rules['first_name'] = 'required|string|max:100';
            $rules['last_name']  = 'required|string|max:100';
            $rules['phone']      = 'required|string|max:20';
        }

        $request->validate($rules);

        $cart = $this->cartService->getByToken($request->cart_token);
        if (!$cart) {
            return response()->json(['message' => 'Cart not found or expired.'], 404);
        }

        if (empty($cart->items)) {
            return response()->json(['message' => 'Cart is empty.'], 422);
        }

        $customer = $isAuthenticated ? $request->user() : null;

        $order = $this->checkoutService->processCheckout($cart, $request->all(), $customer);

        return response()->json([
            'data' => [
                'order'   => $order,
                'message' => 'Order placed successfully.',
            ],
        ], 201);
    }
}
