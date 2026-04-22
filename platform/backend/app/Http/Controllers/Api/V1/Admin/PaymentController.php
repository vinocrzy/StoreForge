<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Payment Management
 *
 * APIs for managing payment transactions and processing refunds.
 */
class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
    ) {}

    /**
     * List payments
     *
     * Get a paginated list of payment transactions for the current store.
     *
     * @authenticated
     *
     * @queryParam page integer Page number. Example: 1
     * @queryParam per_page integer Items per page (max 100). Example: 20
     * @queryParam status string Filter by payment status (pending, completed, failed, refunded). Example: completed
     * @queryParam gateway string Filter by gateway (manual, stripe, razorpay). Example: stripe
     * @queryParam order_id integer Filter by order ID. Example: 42
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "order_id": 42,
     *       "transaction_id": "pi_3xxx",
     *       "gateway": "stripe",
     *       "payment_method": "online",
     *       "amount": "29.98",
     *       "currency": "USD",
     *       "status": "completed",
     *       "failure_reason": null,
     *       "metadata": {},
     *       "processed_at": "2026-04-22T10:00:00Z",
     *       "created_at": "2026-04-22T09:55:00Z",
     *       "order": {
     *         "id": 42,
     *         "order_number": "ORD-1-260422-0001"
     *       }
     *     }
     *   ],
     *   "meta": {
     *     "current_page": 1,
     *     "per_page": 20,
     *     "total": 50
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $payments = Payment::query()
            ->with('order:id,order_number,customer_id,total,payment_status')
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->gateway, fn ($q, $gateway) => $q->where('gateway', $gateway))
            ->when($request->order_id, fn ($q, $orderId) => $q->where('order_id', $orderId))
            ->orderByDesc('created_at')
            ->paginate(min($request->integer('per_page', 20), 100));

        return response()->json($payments);
    }

    /**
     * Process refund
     *
     * Process a refund for a paid order through the configured payment gateway.
     * Only works for orders paid via Stripe or Razorpay (not manual payments).
     *
     * @authenticated
     *
     * @urlParam id integer required The order ID. Example: 42
     *
     * @bodyParam amount float required The amount to refund (must be > 0 and <= order total). Example: 15.00
     * @bodyParam reason string Optional reason for the refund. Example: Customer requested return
     *
     * @response 200 {
     *   "message": "Refund processed successfully.",
     *   "refund": {
     *     "refund_id": "re_xxx",
     *     "status": "succeeded",
     *     "amount": 15.00
     *   }
     * }
     * @response 400 scenario="Not paid" {
     *   "message": "Order is not paid. Cannot process refund."
     * }
     * @response 404 scenario="Order not found" {
     *   "message": "Order not found."
     * }
     * @response 422 scenario="Validation" {
     *   "message": "The amount field is required.",
     *   "errors": {"amount": ["The amount field is required."]}
     * }
     */
    public function refund(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:500',
        ]);

        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        // Validate refund amount doesn't exceed order total
        if ($request->amount > (float) $order->total) {
            return response()->json([
                'message' => 'Refund amount cannot exceed order total.',
                'errors' => ['amount' => ['Refund amount cannot exceed order total of ' . $order->total]],
            ], 422);
        }

        try {
            $result = $this->paymentService->processRefund(
                $order,
                (float) $request->amount,
                $request->reason ?? ''
            );

            return response()->json([
                'message' => 'Refund processed successfully.',
                'refund' => $result,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
