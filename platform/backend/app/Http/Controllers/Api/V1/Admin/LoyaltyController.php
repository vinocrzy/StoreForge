<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\LoyaltyService;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @group Loyalty Program
 *
 * Admin endpoints for managing the loyalty & rewards program.
 */
class LoyaltyController extends Controller
{
    public function __construct(private readonly LoyaltyService $service) {}

    /**
     * Get loyalty program configuration
     *
     * @response 200 {"data": {"enabled": true, "points_per_dollar": 1, "redemption_threshold": 100}}
     */
    public function getConfig(): JsonResponse
    {
        return response()->json(['data' => $this->service->getConfig(tenant()->id)]);
    }

    /**
     * Update loyalty program configuration
     *
     * @bodyParam enabled boolean Whether loyalty program is enabled. Example: true
     * @bodyParam points_per_dollar integer Points earned per $1 spent. Example: 1
     * @bodyParam redemption_threshold integer Minimum points required to redeem. Example: 100
     * @bodyParam points_to_dollar_rate number Dollar value per point. Example: 0.01
     * @bodyParam program_name string Display name for the program. Example: "Honey Rewards"
     */
    public function updateConfig(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'enabled' => 'sometimes|boolean',
            'points_per_dollar' => 'sometimes|integer|min:0',
            'redemption_threshold' => 'sometimes|integer|min:1',
            'points_to_dollar_rate' => 'sometimes|numeric|min:0',
            'program_name' => 'sometimes|string|max:50',
        ]);

        $storeId = tenant()->id;
        $map = [
            'enabled' => 'loyalty_enabled',
            'points_per_dollar' => 'loyalty_points_per_dollar',
            'redemption_threshold' => 'loyalty_redemption_threshold',
            'points_to_dollar_rate' => 'loyalty_points_to_dollar_rate',
            'program_name' => 'loyalty_program_name',
        ];

        foreach ($validated as $key => $value) {
            \DB::table('store_settings')->updateOrInsert(
                ['store_id' => $storeId, 'key' => $map[$key]],
                ['value' => is_bool($value) ? ($value ? 'true' : 'false') : (string) $value, 'updated_at' => now()]
            );
        }

        return response()->json([
            'message' => 'Loyalty program updated.',
            'data' => $this->service->getConfig($storeId),
        ]);
    }

    /**
     * Get customer's point balance and history
     *
     * @urlParam customer_id integer required The customer ID. Example: 1
     */
    public function customerPoints(int $customerId): JsonResponse
    {
        $customer = Customer::findOrFail($customerId);
        $history = $this->service->getHistory($customerId, tenant()->id);

        return response()->json([
            'data' => [
                'customer_id' => $customerId,
                'balance' => $customer->loyalty_points_balance,
                'history' => $history,
            ],
        ]);
    }

    /**
     * Manually adjust a customer's points
     *
     * @urlParam customer_id integer required The customer ID. Example: 1
     * @bodyParam points integer required Points to add (positive) or deduct (negative). Example: 50
     * @bodyParam reason string required Reason for the adjustment. Example: "Goodwill gesture"
     */
    public function adjustPoints(Request $request, int $customerId): JsonResponse
    {
        $validated = $request->validate([
            'points' => 'required|integer',
            'reason' => 'required|string|max:255',
        ]);

        $transaction = $this->service->adjustPoints(
            $customerId,
            tenant()->id,
            $validated['points'],
            $validated['reason']
        );

        return response()->json([
            'message' => 'Points adjusted.',
            'data' => $transaction,
        ]);
    }
}
