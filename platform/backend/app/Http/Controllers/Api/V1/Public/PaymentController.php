<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Public Storefront — Payments
 *
 * Create payment intents for orders during checkout.
 */
class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
    ) {}

    /**
     * Create payment intent
     *
     * Creates a payment intent (Stripe) or order (Razorpay) for the given order.
     * The customer must own the order. Returns the client-side data needed to
     * complete the payment on the frontend.
     *
     * @authenticated
     *
     * @bodyParam order_id integer required The order ID to pay for. Example: 42
     *
     * @response 200 scenario="Stripe" {
     *   "client_secret": "pi_xxx_secret_yyy",
     *   "gateway": "stripe"
     * }
     * @response 200 scenario="Razorpay" {
     *   "razorpay_order_id": "order_xxx",
     *   "key_id": "rzp_test_xxx",
     *   "amount": 2998,
     *   "currency": "INR",
     *   "gateway": "razorpay"
     * }
     * @response 400 scenario="No gateway" {
     *   "message": "No payment gateway configured for this store. Use manual payment."
     * }
     * @response 403 scenario="Not your order" {
     *   "message": "You are not authorized to pay for this order."
     * }
     * @response 404 scenario="Order not found" {
     *   "message": "Order not found."
     * }
     * @response 422 scenario="Already paid" {
     *   "message": "Order is already paid."
     * }
     */
    public function createIntent(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|integer',
        ]);

        $order = Order::find($request->order_id);

        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        // Ensure the authenticated customer owns this order
        $customer = $request->user();
        if ($order->customer_id !== $customer->id) {
            return response()->json(['message' => 'You are not authorized to pay for this order.'], 403);
        }

        try {
            $result = $this->paymentService->createPaymentIntent($order);

            return response()->json($result);
        } catch (\RuntimeException $e) {
            $code = str_contains($e->getMessage(), 'already paid') ? 422 : 400;
            return response()->json(['message' => $e->getMessage()], $code);
        }
    }
}
