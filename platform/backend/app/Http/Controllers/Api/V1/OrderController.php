<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderRequest;
use App\Http\Requests\PaymentRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Order Management
 *
 * APIs for managing orders, order status, and payments
 */
class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * List orders
     *
     * Get paginated list of orders with filtering options.
     *
     * @queryParam page integer Page number for pagination. Example: 1
     * @queryParam per_page integer Items per page (max 100). Example: 20
     * @queryParam status string Filter by order status. Example: pending
     * @queryParam payment_status string Filter by payment status. Example: paid
     * @queryParam search string Search by order number or customer name. Example: ORD-001
     * @queryParam customer_id integer Filter by customer ID. Example: 5
     *
     * @response 200 {
     *  "data": [
     *    {
     *      "id": 1,
     *      "order_number": "ORD-1-240406-0001",
     *      "customer_id": 5,
     *      "status": "confirmed",
     *      "payment_status": "paid",
     *      "fulfillment_status": "unfulfilled",
     *      "subtotal": "150.00",
     *      "discount_amount": "10.00",
     *      "shipping_amount": "15.00",
     *      "tax_amount": "15.50",
     *      "total": "170.50",
     *      "currency": "USD",
     *      "placed_at": "2024-04-06T10:30:00Z",
     *      "customer": {
     *        "id": 5,
     *        "first_name": "John",
     *        "last_name": "Doe",
     *        "email": "john@example.com"
     *      }
     *    }
     *  ],
     *  "meta": {"current_page": 1, "per_page": 20, "total": 45}
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->input('per_page', 20), 100);
        
        $orders = Order::with(['customer', 'items'])
            ->when($request->status, fn($q) => $q->status($request->status))
            ->when($request->payment_status, fn($q) => $q->paymentStatus($request->payment_status))
            ->when($request->customer_id, fn($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->search, fn($q) => $q->search($request->search))
            ->latest('created_at')
            ->paginate($perPage);
        
        return response()->json($orders);
    }

    /**
     * Create order
     *
     * Create a new order with items.
     *
     * @bodyParam customer_id integer required Customer ID. Example: 5
     * @bodyParam items array required Array of order items.
     * @bodyParam items.*.product_id integer required Product ID. Example: 10
     * @bodyParam items.*.quantity integer required Quantity. Example: 2
     * @bodyParam items.*.price number Optional price override. Example: 99.99
     * @bodyParam items.*.discount_amount number Optional item discount. Example: 10.00
     * @bodyParam customer_note string Optional customer note. Example: Please gift wrap
     * @bodyParam admin_note string Optional admin note. Example: Priority order
     * @bodyParam coupon_code string Optional coupon code. Example: SAVE10
     * @bodyParam payment_method string Payment method. Example: bank_transfer
     * @bodyParam shipping_amount number Optional shipping cost override. Example: 10.00
     * @bodyParam billing_address_id integer Optional billing address ID. Example: 3
     * @bodyParam shipping_address_id integer Optional shipping address ID. Example: 3
     *
     * @response 201 {
     *  "data": {
     *    "id": 1,
     *    "order_number": "ORD-1-240406-0001",
     *    "customer_id": 5,
     *    "status": "pending",
     *    "payment_status": "pending",
     *    "total": "170.50",
     *    "items": []
     *  }
     * }
     */
    public function store(OrderRequest $request): JsonResponse
    {
        $order = $this->orderService->createOrder($request->validated());
        
        return response()->json(['data' => $order->load(['items.product', 'customer', 'payments'])], 201);
    }

    /**
     * Get order details
     *
     * Retrieve detailed information about a specific order.
     *
     * @urlParam id integer required Order ID. Example: 1
     *
     * @response 200 {
     *  "data": {
     *    "id": 1,
     *    "order_number": "ORD-1-240406-0001",
     *    "customer": {},
     *    "items": [],
     *    "payments": [],
     *    "status": "confirmed",
     *    "payment_status": "paid",
     *    "total": "170.50"
     *  }
     * }
     */
    public function show(Order $order): JsonResponse
    {
        return response()->json([
            'data' => $order->load(['customer', 'items.product', 'payments'])
        ]);
    }

    /**
     * Update order
     *
     * Update order details (not for status changes, use dedicated endpoints).
     *
     * @urlParam id integer required Order ID. Example: 1
     * @bodyParam customer_note string Optional customer note. Example: Updated note
     * @bodyParam admin_note string Optional admin note. Example: Admin updated
     * @bodyParam shipping_amount number Optional shipping cost. Example: 12.50
     *
     * @response 200 {
     *  "data": {"id": 1, "status": "pending", "admin_note": "Admin updated"}
     * }
     */
    public function update(OrderRequest $request, Order $order): JsonResponse
    {
        $order->update($request->validated());
        
        return response()->json(['data' => $order->fresh()]);
    }

    /**
     * Delete order
     *
     * Soft delete an order.
     *
     * @urlParam id integer required Order ID. Example: 1
     *
     * @response 204 {}
     */
    public function destroy(Order $order): JsonResponse
    {
        $order->delete();
        
        return response()->json(null, 204);
    }

    /**
     * Update order status
     *
     * Change order status (pending, confirmed, processing, shipped, delivered, cancelled).
     *
     * @urlParam id integer required Order ID. Example: 1
     * @bodyParam status string required New status. Example: confirmed
     *
     * @response 200 {
     *  "data": {"id": 1, "status": "confirmed", "confirmed_at": "2024-04-06T10:30:00Z"}
     * }
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled,refunded',
        ]);
        
        $updatedOrder = $this->orderService->updateOrderStatus($order, $request->status);
        
        return response()->json(['data' => $updatedOrder]);
    }

    /**
     * Cancel order
     *
     * Cancel an order and release inventory if fulfilled.
     *
     * @urlParam id integer required Order ID. Example: 1
     * @bodyParam reason string Optional cancellation reason. Example: Customer requested
     *
     * @response 200 {
     *  "data": {"id": 1, "status": "cancelled", "cancelled_at": "2024-04-06T10:30:00Z"}
     * }
     */
    public function cancel(Request $request, Order $order): JsonResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);
        
        try {
            $cancelledOrder = $this->orderService->cancelOrder($order, $request->reason);
            
            return response()->json(['data' => $cancelledOrder]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Record payment
     *
     * Record a manual payment for an order.
     *
     * @urlParam id integer required Order ID. Example: 1
     * @bodyParam payment_method string required Payment method. Example: bank_transfer
     * @bodyParam amount number required Payment amount. Example: 170.50
     * @bodyParam transaction_id string Optional transaction/reference ID. Example: TXN-123456
     * @bodyParam payment_notes string Optional payment notes. Example: Received via bank transfer
     * @bodyParam metadata object Optional payment metadata. Example: {"bank": "Chase", "ref": "123"}
     *
     * @response 201 {
     *  "data": {
     *    "id": 1,
     *    "order_id": 1,
     *    "amount": "170.50",
     *    "status": "completed",
     *    "gateway": "manual"
     *  }
     * }
     */
    public function recordPayment(PaymentRequest $request, Order $order): JsonResponse
    {
        $paymentData = array_merge($request->validated(), [
            'order_id' => $order->id,
            'paid_by_user_id' => auth()->id(),
        ]);
        
        $payment = $this->orderService->recordPayment($order, $paymentData);
        
        return response()->json(['data' => $payment], 201);
    }

    /**
     * Fulfill order
     *
     * Mark order as fulfilled and adjust inventory.
     *
     * @urlParam id integer required Order ID. Example: 1
     *
     * @response 200 {
     *  "message": "Order fulfilled successfully",
     *  "data": {"id": 1, "fulfillment_status": "fulfilled"}
     * }
     */
    public function fulfill(Order $order): JsonResponse
    {
        try {
            $this->orderService->fulfillOrder($order);
            
            return response()->json([
                'message' => 'Order fulfilled successfully',
                'data' => $order->fresh(['items', 'customer']),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Get order statistics
     *
     * Get order and revenue statistics for the current store.
     *
     * @response 200 {
     *  "data": {
     *    "total_orders": 45,
     *    "pending_orders": 5,
     *    "processing_orders": 8,
     *    "shipped_orders": 10,
     *    "delivered_orders": 20,
     *    "cancelled_orders": 2,
     *    "total_revenue": "12500.50",
     *    "pending_payments": "350.00"
     *  }
     * }
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->orderService->getOrderStatistics();
        
        return response()->json(['data' => $stats]);
    }
}
