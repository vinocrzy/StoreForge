<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Public Storefront
 *
 * Customer account management for authenticated customers.
 *
 * @authenticated
 */
class CustomerAccountController extends Controller
{
    /**
     * Get profile
     *
     * Retrieve the authenticated customer's profile details.
     *
     * @response 200 scenario="Success" {
     *   "data": {"id": 1, "first_name": "Jane", "last_name": "Doe", "email": "jane@example.com", "phone": "+12025551234"}
     * }
     */
    public function profile(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()]);
    }

    /**
     * Update profile
     *
     * Update the authenticated customer's profile details.
     *
     * @bodyParam first_name string First name. Example: Jane
     * @bodyParam last_name string Last name. Example: Doe
     * @bodyParam phone string Phone number (E.164 format). Example: +12025551234
     * @bodyParam date_of_birth string Date of birth (Y-m-d). Example: 1990-01-15
     *
     * @response 200 scenario="Success" {"data": {"id": 1, "first_name": "Jane"}}
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $customer = $request->user();
        $storeId  = tenant()->id;

        $request->validate([
            'first_name'    => 'sometimes|string|max:100',
            'last_name'     => 'sometimes|string|max:100',
            'phone'         => "sometimes|string|max:20|unique:customers,phone,{$customer->id},id,store_id,{$storeId}",
            'date_of_birth' => 'sometimes|nullable|date|before:today',
        ]);

        $customer->update($request->only(['first_name', 'last_name', 'phone', 'date_of_birth']));

        return response()->json(['data' => $customer->fresh()]);
    }

    /**
     * List orders
     *
     * Retrieve the authenticated customer's order history, newest first.
     *
     * @queryParam per_page integer Items per page (max 50). Example: 10
     *
     * @response 200 scenario="Success" {
     *   "data": [{"id": 1, "order_number": "ORD-ABC12345", "status": "pending", "total": "29.98"}],
     *   "meta": {"current_page": 1, "per_page": 10, "total": 5}
     * }
     */
    public function orders(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 10), 50);

        $orders = Order::where('customer_id', $request->user()->id)
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($orders);
    }

    /**
     * Get order detail
     *
     * Retrieve full details for a specific order belonging to the authenticated customer.
     *
     * @urlParam id integer required Order ID. Example: 42
     *
     * @response 200 scenario="Success" {
     *   "data": {"id": 42, "order_number": "ORD-ABC12345", "status": "pending", "items": []}
     * }
     * @response 404 scenario="Not found or not owned" {"message": "No query results for model [App\\Models\\Order]."}
     */
    public function orderDetail(Request $request, int $id): JsonResponse
    {
        $order = Order::where('id', $id)
            ->where('customer_id', $request->user()->id)
            ->with(['items', 'payments'])
            ->firstOrFail();

        return response()->json(['data' => $order]);
    }
}
