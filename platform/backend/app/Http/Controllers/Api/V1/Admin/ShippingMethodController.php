<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreShippingMethodRequest;
use App\Http\Requests\Admin\UpdateShippingMethodRequest;
use App\Models\ShippingMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Shipping Methods (Admin)
 *
 * CRUD for shipping methods available on the store.
 *
 * @authenticated
 */
class ShippingMethodController extends Controller
{
    /**
     * List shipping methods
     *
     * @queryParam include_inactive bool Include inactive methods. Example: false
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1, "name": "Standard Shipping", "type": "flat_rate",
     *       "rate": "5.99", "is_active": true, "display_order": 0
     *     }
     *   ]
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $query = ShippingMethod::query()
            ->orderBy('display_order')
            ->orderBy('name');

        if (!$request->boolean('include_inactive')) {
            $query->where('is_active', true);
        }

        return response()->json(['data' => $query->get()]);
    }

    /**
     * Create shipping method
     *
     * @bodyParam name string required Method name. Example: Standard Shipping
     * @bodyParam type string required Type: flat_rate, weight_based, free_above, local_pickup. Example: flat_rate
     * @bodyParam rate number Shipping cost or per-kg rate. Example: 5.99
     * @bodyParam free_above number Cart total above which shipping is free. Example: 100.00
     * @bodyParam is_active boolean. Example: true
     * @bodyParam display_order integer Sort order. Example: 0
     *
     * @response 201 {"data": {"id": 1, "name": "Standard Shipping"}, "message": "Shipping method created."}
     */
    public function store(StoreShippingMethodRequest $request): JsonResponse
    {
        $method = ShippingMethod::create($request->validated());

        return response()->json(['data' => $method, 'message' => 'Shipping method created.'], 201);
    }

    /**
     * Show shipping method
     *
     * @response 200 {"data": {"id": 1, "name": "Standard Shipping"}}
     */
    public function show(int $id): JsonResponse
    {
        $method = ShippingMethod::findOrFail($id);

        return response()->json(['data' => $method]);
    }

    /**
     * Update shipping method
     *
     * @bodyParam name string Method name. Example: Express Shipping
     * @bodyParam rate number New rate. Example: 9.99
     *
     * @response 200 {"data": {"id": 1}, "message": "Shipping method updated."}
     */
    public function update(UpdateShippingMethodRequest $request, int $id): JsonResponse
    {
        $method = ShippingMethod::findOrFail($id);
        $method->update($request->validated());

        return response()->json(['data' => $method, 'message' => 'Shipping method updated.']);
    }

    /**
     * Delete shipping method
     *
     * @response 200 {"message": "Shipping method deleted."}
     */
    public function destroy(int $id): JsonResponse
    {
        $method = ShippingMethod::findOrFail($id);
        $method->delete();

        return response()->json(['message' => 'Shipping method deleted.']);
    }
}
