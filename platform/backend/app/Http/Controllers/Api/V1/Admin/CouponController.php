<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Coupons (Admin)
 *
 * Admin endpoints for managing discount coupons. Create, update, list,
 * and delete coupons with full usage statistics.
 *
 * @authenticated
 */
class CouponController extends Controller
{
    /**
     * List coupons
     *
     * Retrieve paginated coupons with optional status and search filters.
     *
     * @queryParam status string Filter by status: active, inactive, expired. Example: active
     * @queryParam search string Search by coupon code. Example: SUMMER
     * @queryParam per_page integer Items per page (max 50). Example: 20
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "code": "SUMMER20",
     *       "type": "percentage",
     *       "value": "20.00",
     *       "status": "active",
     *       "usage_limit": 100,
     *       "used_count": 15,
     *       "usage_limit_per_customer": 1,
     *       "minimum_purchase_amount": "50.00",
     *       "maximum_discount_amount": "25.00",
     *       "starts_at": "2026-04-01T00:00:00.000000Z",
     *       "expires_at": "2026-06-30T23:59:59.000000Z",
     *       "created_at": "2026-04-01T10:00:00.000000Z"
     *     }
     *   ],
     *   "meta": {"current_page": 1, "per_page": 20, "total": 5}
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 20), 50);

        $coupons = Coupon::query()
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->input('status'));
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('code', 'like', '%' . strtoupper($request->input('search')) . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($coupons);
    }

    /**
     * Create coupon
     *
     * Create a new discount coupon for the current store.
     *
     * @bodyParam code string required Coupon code (stored uppercase). Example: SUMMER20
     * @bodyParam type string required Discount type: percentage or fixed. Example: percentage
     * @bodyParam value number required Discount value (percentage or fixed amount). Example: 20.00
     * @bodyParam status string Status: active or inactive. Example: active
     * @bodyParam usage_limit integer Max total uses. Example: 100
     * @bodyParam usage_limit_per_customer integer Max uses per customer. Example: 1
     * @bodyParam minimum_purchase_amount number Minimum cart subtotal required. Example: 50.00
     * @bodyParam maximum_discount_amount number Maximum discount cap (for percentage coupons). Example: 25.00
     * @bodyParam starts_at string Coupon start date (ISO 8601). Example: 2026-04-01T00:00:00Z
     * @bodyParam expires_at string Coupon expiry date (ISO 8601). Example: 2026-06-30T23:59:59Z
     *
     * @response 201 scenario="Created" {
     *   "message": "Coupon created",
     *   "data": {
     *     "id": 1,
     *     "code": "SUMMER20",
     *     "type": "percentage",
     *     "value": "20.00",
     *     "status": "active"
     *   }
     * }
     * @response 422 scenario="Validation error" {
     *   "message": "The given data was invalid.",
     *   "errors": {"code": ["The code has already been taken."]}
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0.01',
            'status' => 'sometimes|in:active,inactive',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_customer' => 'nullable|integer|min:1',
            'minimum_purchase_amount' => 'nullable|numeric|min:0',
            'maximum_discount_amount' => 'nullable|numeric|min:0',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));

        // Check uniqueness within store
        $exists = Coupon::where('code', $validated['code'])->exists();
        if ($exists) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['code' => ['The code has already been taken.']],
            ], 422);
        }

        $coupon = Coupon::create($validated);

        return response()->json([
            'message' => 'Coupon created',
            'data' => $coupon,
        ], 201);
    }

    /**
     * Get coupon details
     *
     * Retrieve a single coupon with usage statistics.
     *
     * @urlParam id integer required Coupon ID. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": 1,
     *     "code": "SUMMER20",
     *     "type": "percentage",
     *     "value": "20.00",
     *     "status": "active",
     *     "usage_limit": 100,
     *     "used_count": 15,
     *     "usage_limit_per_customer": 1,
     *     "minimum_purchase_amount": "50.00",
     *     "maximum_discount_amount": "25.00",
     *     "starts_at": "2026-04-01T00:00:00.000000Z",
     *     "expires_at": "2026-06-30T23:59:59.000000Z",
     *     "total_uses": 15,
     *     "total_discount_given": "375.00",
     *     "recent_usages": [
     *       {
     *         "id": 10,
     *         "discount_amount": "25.00",
     *         "created_at": "2026-04-20T14:30:00.000000Z",
     *         "order": {"id": 50, "order_number": "ORD-1-260420-5050"},
     *         "customer": {"id": 3, "first_name": "Jane", "last_name": "Doe"}
     *       }
     *     ]
     *   }
     * }
     * @response 404 scenario="Not found" {
     *   "message": "Coupon not found"
     * }
     */
    public function show(int $id): JsonResponse
    {
        $coupon = Coupon::find($id);

        if (!$coupon) {
            return response()->json(['message' => 'Coupon not found'], 404);
        }

        $recentUsages = $coupon->usages()
            ->with([
                'order:id,order_number',
                'customer:id,first_name,last_name',
            ])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $totalDiscountGiven = $coupon->usages()->sum('discount_amount');

        $data = $coupon->toArray();
        $data['total_uses'] = $coupon->used_count;
        $data['total_discount_given'] = number_format((float) $totalDiscountGiven, 2, '.', '');
        $data['recent_usages'] = $recentUsages;

        return response()->json(['data' => $data]);
    }

    /**
     * Update coupon
     *
     * Update an existing coupon's details.
     *
     * @urlParam id integer required Coupon ID. Example: 1
     * @bodyParam code string Coupon code (stored uppercase). Example: WINTER25
     * @bodyParam type string Discount type: percentage or fixed. Example: fixed
     * @bodyParam value number Discount value. Example: 10.00
     * @bodyParam status string Status: active, inactive. Example: inactive
     * @bodyParam usage_limit integer Max total uses. Example: 200
     * @bodyParam usage_limit_per_customer integer Max uses per customer. Example: 2
     * @bodyParam minimum_purchase_amount number Minimum cart subtotal required. Example: 30.00
     * @bodyParam maximum_discount_amount number Maximum discount cap. Example: 50.00
     * @bodyParam starts_at string Coupon start date (ISO 8601). Example: 2026-05-01T00:00:00Z
     * @bodyParam expires_at string Coupon expiry date (ISO 8601). Example: 2026-12-31T23:59:59Z
     *
     * @response 200 scenario="Updated" {
     *   "message": "Coupon updated",
     *   "data": {"id": 1, "code": "WINTER25", "value": "10.00"}
     * }
     * @response 404 scenario="Not found" {
     *   "message": "Coupon not found"
     * }
     * @response 422 scenario="Validation error" {
     *   "message": "The given data was invalid.",
     *   "errors": {"code": ["The code has already been taken."]}
     * }
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $coupon = Coupon::find($id);

        if (!$coupon) {
            return response()->json(['message' => 'Coupon not found'], 404);
        }

        $validated = $request->validate([
            'code' => 'sometimes|string|max:50',
            'type' => 'sometimes|in:percentage,fixed',
            'value' => 'sometimes|numeric|min:0.01',
            'status' => 'sometimes|in:active,inactive',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_customer' => 'nullable|integer|min:1',
            'minimum_purchase_amount' => 'nullable|numeric|min:0',
            'maximum_discount_amount' => 'nullable|numeric|min:0',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        if (isset($validated['code'])) {
            $validated['code'] = strtoupper(trim($validated['code']));

            // Check uniqueness within store, excluding current coupon
            $exists = Coupon::where('code', $validated['code'])
                ->where('id', '!=', $coupon->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['code' => ['The code has already been taken.']],
                ], 422);
            }
        }

        $coupon->update($validated);

        return response()->json([
            'message' => 'Coupon updated',
            'data' => $coupon->fresh(),
        ]);
    }

    /**
     * Delete coupon
     *
     * Soft-delete a coupon. Existing usages are preserved.
     *
     * @urlParam id integer required Coupon ID. Example: 1
     *
     * @response 200 scenario="Deleted" {
     *   "message": "Coupon deleted"
     * }
     * @response 404 scenario="Not found" {
     *   "message": "Coupon not found"
     * }
     */
    public function destroy(int $id): JsonResponse
    {
        $coupon = Coupon::find($id);

        if (!$coupon) {
            return response()->json(['message' => 'Coupon not found'], 404);
        }

        $coupon->delete();

        return response()->json(['message' => 'Coupon deleted']);
    }
}
