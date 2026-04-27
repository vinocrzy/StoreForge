<?php

namespace App\Http\Controllers\Api\V1\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateReturnRequest;
use App\Models\ReturnRequest;
use App\Services\ReturnService;
use Illuminate\Http\JsonResponse;

/**
 * @group Returns (Customer)
 *
 * Customer endpoints for creating and viewing return requests.
 *
 * @authenticated
 */
class ReturnController extends Controller
{
    public function __construct(private ReturnService $returnService) {}

    /**
     * Create return request
     *
     * Submit a return request for a delivered order.
     *
     * @bodyParam order_id int required The order to return. Example: 42
     * @bodyParam reason string required Reason: damaged, wrong_item, not_as_described, changed_mind, other. Example: damaged
     * @bodyParam reason_details string Additional details. Example: The item arrived broken.
     * @bodyParam items array List of specific items to return.
     * @bodyParam items[].order_item_id int required. Example: 7
     * @bodyParam items[].quantity int required. Example: 1
     *
     * @response 201 {
     *   "data": {
     *     "id": 1, "return_number": "RET-1-1714200000",
     *     "status": "requested", "reason": "damaged"
     *   },
     *   "message": "Return request submitted."
     * }
     */
    public function store(CreateReturnRequest $request): JsonResponse
    {
        $customer = $request->user();

        $return = $this->returnService->createReturn($request->validated(), $customer->id);

        return response()->json([
            'data'    => $return,
            'message' => 'Return request submitted.',
        ], 201);
    }

    /**
     * List my returns
     *
     * Returns all return requests submitted by the authenticated customer.
     *
     * @response 200 {"data": [{"id": 1, "return_number": "RET-1-xxx", "status": "requested"}]}
     */
    public function index(): JsonResponse
    {
        $customer = request()->user();

        $returns = ReturnRequest::where('customer_id', $customer->id)
            ->with(['order:id,order_number', 'items'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $returns]);
    }

    /**
     * Show return details
     *
     * @urlParam id int required Return ID. Example: 1
     *
     * @response 200 {"data": {"id": 1, "status": "requested", "items": []}}
     */
    public function show(int $id): JsonResponse
    {
        $customer = request()->user();

        $return = ReturnRequest::where('customer_id', $customer->id)
            ->with(['order', 'items.orderItem'])
            ->findOrFail($id);

        return response()->json(['data' => $return]);
    }
}
