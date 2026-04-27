<?php

namespace App\Http\Controllers\Api\V1\Storefront;

use App\Http\Controllers\Controller;
use App\Services\LoyaltyService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @group Customer Loyalty
 *
 * Storefront endpoints for the loyalty & rewards program.
 */
class LoyaltyController extends Controller
{
    public function __construct(private readonly LoyaltyService $service) {}

    /**
     * Get loyalty balance and program info
     *
     * Returns the authenticated customer's point balance and program configuration.
     *
     * @response 200 {"data": {"balance": 250, "program": {"enabled": true, "program_name": "Rewards", "redemption_threshold": 100, "points_to_dollar_rate": 0.01}}}
     */
    public function balance(Request $request): JsonResponse
    {
        $customer = $request->user();
        $storeId = tenant()->id;
        $config = $this->service->getConfig($storeId);

        return response()->json([
            'data' => [
                'balance' => $customer->loyalty_points_balance ?? 0,
                'program' => [
                    'enabled' => $config['enabled'],
                    'program_name' => $config['program_name'],
                    'redemption_threshold' => $config['redemption_threshold'],
                    'points_to_dollar_rate' => $config['points_to_dollar_rate'],
                    'min_discount' => round($config['redemption_threshold'] * $config['points_to_dollar_rate'], 2),
                ],
            ],
        ]);
    }

    /**
     * Get customer's point history
     *
     * @response 200 {"data": [{"points": 100, "type": "earned_order", "description": "Earned for order #ORD-001", "created_at": "..."}]}
     */
    public function history(Request $request): JsonResponse
    {
        $customer = $request->user();
        $history = $this->service->getHistory($customer->id, tenant()->id);

        return response()->json(['data' => $history]);
    }

    /**
     * Validate points redemption at checkout
     *
     * @bodyParam points integer required Points to redeem. Example: 100
     *
     * @response 200 {"data": {"valid": true, "discount_amount": 1.00, "new_balance": 150}}
     * @response 200 {"data": {"valid": false, "message": "Insufficient points balance."}}
     */
    public function validateRedemption(Request $request): JsonResponse
    {
        $request->validate([
            'points' => 'required|integer|min:1',
        ]);

        $customer = $request->user();
        $result = $this->service->validateRedemption(
            $customer->id,
            tenant()->id,
            $request->input('points')
        );

        return response()->json(['data' => $result]);
    }
}
