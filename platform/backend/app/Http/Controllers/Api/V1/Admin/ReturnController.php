<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveReturnRequest;
use App\Models\ReturnRequest;
use App\Services\ReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Returns & Refunds (Admin)
 *
 * Admin endpoints for managing customer return and refund requests (RMA).
 *
 * @authenticated
 */
class ReturnController extends Controller
{
    public function __construct(private ReturnService $returnService) {}

    /**
     * List returns
     *
     * Returns paginated return requests, optionally filtered by status.
     *
     * @queryParam status string Filter by status: requested, approved, rejected, received, refunded. Example: requested
     * @queryParam per_page int Items per page (max 50). Example: 20
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1, "return_number": "RET-1-1714200000",
     *       "status": "requested", "reason": "damaged",
     *       "refund_amount": null, "created_at": "2026-04-27T10:00:00Z"
     *     }
     *   ],
     *   "meta": {"current_page": 1, "per_page": 20, "total": 5}
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 20), 50);

        $returns = ReturnRequest::query()
            ->with(['order:id,order_number', 'customer:id,first_name,last_name,email'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json($returns);
    }

    /**
     * Show return
     *
     * Get full details for a single return request including items.
     *
     * @urlParam id int required Return ID. Example: 1
     *
     * @response 200 {"data": {"id": 1, "items": [], "order": {}, "customer": {}}}
     */
    public function show(int $id): JsonResponse
    {
        $return = ReturnRequest::with(['order', 'customer', 'items.orderItem'])->findOrFail($id);

        return response()->json(['data' => $return]);
    }

    /**
     * Approve return
     *
     * Approve the return and set the refund amount to be issued.
     *
     * @urlParam id int required Return ID. Example: 1
     * @bodyParam refund_amount number required Refund amount in store currency. Example: 39.99
     * @bodyParam notes string Optional admin notes. Example: Approved after inspection.
     *
     * @response 200 {"data": {"id": 1, "status": "approved"}, "message": "Return approved."}
     */
    public function approve(ApproveReturnRequest $request, int $id): JsonResponse
    {
        $return = ReturnRequest::findOrFail($id);

        if (!in_array($return->status, ['requested', 'received'])) {
            return response()->json(['message' => 'Return cannot be approved in its current status.'], 422);
        }

        $updated = $this->returnService->approveReturn(
            $return,
            (float) $request->validated('refund_amount'),
            $request->validated('notes', '')
        );

        return response()->json(['data' => $updated, 'message' => 'Return approved.']);
    }

    /**
     * Reject return
     *
     * Reject the return request with optional notes.
     *
     * @urlParam id int required Return ID. Example: 1
     * @bodyParam notes string Reason for rejection. Example: Item shows signs of misuse.
     *
     * @response 200 {"data": {"id": 1, "status": "rejected"}, "message": "Return rejected."}
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $return = ReturnRequest::findOrFail($id);

        if ($return->status !== 'requested') {
            return response()->json(['message' => 'Return cannot be rejected in its current status.'], 422);
        }

        $updated = $this->returnService->rejectReturn($return, $request->input('notes', ''));

        return response()->json(['data' => $updated, 'message' => 'Return rejected.']);
    }

    /**
     * Process refund
     *
     * Process the refund via the store's payment gateway for an approved return.
     *
     * @urlParam id int required Return ID. Example: 1
     *
     * @response 200 {"data": {"refund_id": "re_xxx"}, "message": "Refund processed successfully."}
     */
    public function processRefund(int $id): JsonResponse
    {
        $return = ReturnRequest::findOrFail($id);

        $result = $this->returnService->processRefund($return);

        return response()->json(['data' => $result, 'message' => 'Refund processed successfully.']);
    }
}
