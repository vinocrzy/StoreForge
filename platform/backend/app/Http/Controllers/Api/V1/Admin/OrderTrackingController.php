<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateTrackingRequest;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

/**
 * @group Order Tracking (Admin)
 *
 * Update shipment tracking information on orders.
 *
 * @authenticated
 */
class OrderTrackingController extends Controller
{
    /**
     * Update order tracking
     *
     * Attach tracking number, carrier, and optional tracking URL to an order.
     *
     * @urlParam id int required Order ID. Example: 42
     * @bodyParam tracking_number string required Carrier tracking number. Example: 1Z999AA10123456784
     * @bodyParam tracking_carrier string Carrier name. Example: UPS
     * @bodyParam tracking_url string Full tracking URL. Example: https://www.ups.com/track?tracknum=1Z999AA10123456784
     * @bodyParam estimated_delivery_at string ISO 8601 estimated delivery date. Example: 2026-05-05T00:00:00Z
     *
     * @response 200 {"data": {"id": 42, "tracking_number": "1Z999AA10123456784"}, "message": "Tracking updated."}
     */
    public function update(UpdateTrackingRequest $request, int $id): JsonResponse
    {
        $order = Order::findOrFail($id);

        $order->update($request->validated());

        return response()->json([
            'data'    => $order->only(['id', 'order_number', 'tracking_number', 'tracking_carrier', 'tracking_url', 'estimated_delivery_at']),
            'message' => 'Tracking updated.',
        ]);
    }
}
