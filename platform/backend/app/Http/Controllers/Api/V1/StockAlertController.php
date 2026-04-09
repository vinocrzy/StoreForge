<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StockAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Stock Alerts
 *
 * Manage low stock and out-of-stock alerts for the authenticated store.
 *
 * @authenticated
 */
class StockAlertController extends Controller
{
    /**
     * List stock alerts
     *
     * Get active/resolved stock alerts with optional filtering.
     *
     * @queryParam status string Filter by alert status: active, resolved. Example: active
     * @queryParam alert_type string Filter by alert type: low_stock, out_of_stock. Example: low_stock
     * @queryParam per_page integer Items per page (max 100). Example: 20
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "alert_type": "low_stock",
     *       "threshold": 10,
     *       "current_quantity": 4,
     *       "status": "active"
     *     }
     *   ]
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $query = StockAlert::query()->with(['product', 'warehouse']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('alert_type')) {
            $query->where('alert_type', $request->input('alert_type'));
        }

        $query->orderBy('created_at', 'desc');

        $perPage = min((int) $request->input('per_page', 20), 100);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Resolve stock alert
     *
     * Mark a stock alert as resolved.
     *
     * @urlParam id integer required Alert ID. Example: 1
     *
     * @response 200 {
     *   "message": "Stock alert resolved successfully",
     *   "data": {
     *     "id": 1,
     *     "status": "resolved"
     *   }
     * }
     */
    public function resolve(int $id): JsonResponse
    {
        $alert = StockAlert::findOrFail($id);

        $alert->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Stock alert resolved successfully',
            'data' => $alert->fresh(['product', 'warehouse']),
        ]);
    }
}
