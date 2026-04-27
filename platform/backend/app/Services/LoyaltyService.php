<?php

namespace App\Services;

use App\Models\LoyaltyPoint;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Coupon;
use Illuminate\Support\Facades\DB;

class LoyaltyService
{
    /**
     * Get loyalty program configuration from store settings.
     */
    public function getConfig(int $storeId): array
    {
        $settings = DB::table('store_settings')
            ->where('store_id', $storeId)
            ->whereIn('key', [
                'loyalty_enabled',
                'loyalty_points_per_dollar',
                'loyalty_points_per_purchase',
                'loyalty_redemption_threshold',
                'loyalty_points_to_dollar_rate',
                'loyalty_program_name',
            ])
            ->pluck('value', 'key')
            ->toArray();

        return [
            'enabled' => filter_var($settings['loyalty_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'points_per_dollar' => (int) ($settings['loyalty_points_per_dollar'] ?? 1),
            'redemption_threshold' => (int) ($settings['loyalty_redemption_threshold'] ?? 100),
            'points_to_dollar_rate' => (float) ($settings['loyalty_points_to_dollar_rate'] ?? 0.01),
            'program_name' => $settings['loyalty_program_name'] ?? 'Rewards',
        ];
    }

    /**
     * Award points for a completed (delivered) order.
     */
    public function awardOrderPoints(Order $order): ?LoyaltyPoint
    {
        $config = $this->getConfig($order->store_id);

        if (!$config['enabled'] || !$order->customer_id) {
            return null;
        }

        $pointsToAward = (int) floor($order->total_amount * $config['points_per_dollar']);

        if ($pointsToAward <= 0) {
            return null;
        }

        return DB::transaction(function () use ($order, $pointsToAward) {
            $customer = Customer::withoutGlobalScope('store')->find($order->customer_id);
            if (!$customer) return null;

            $newBalance = $customer->loyalty_points_balance + $pointsToAward;

            $customer->update(['loyalty_points_balance' => $newBalance]);

            return LoyaltyPoint::create([
                'store_id' => $order->store_id,
                'customer_id' => $order->customer_id,
                'points' => $pointsToAward,
                'type' => 'earned_order',
                'description' => "Earned for order #{$order->order_number}",
                'source_type' => Order::class,
                'source_id' => $order->id,
                'balance_after' => $newBalance,
            ]);
        });
    }

    /**
     * Validate if customer can redeem a given number of points.
     */
    public function validateRedemption(int $customerId, int $storeId, int $pointsToRedeem): array
    {
        $config = $this->getConfig($storeId);

        if (!$config['enabled']) {
            return ['valid' => false, 'message' => 'Loyalty program is not enabled.'];
        }

        if ($pointsToRedeem < $config['redemption_threshold']) {
            return [
                'valid' => false,
                'message' => "Minimum redemption is {$config['redemption_threshold']} points.",
            ];
        }

        $customer = Customer::withoutGlobalScope('store')->find($customerId);
        if (!$customer || $customer->loyalty_points_balance < $pointsToRedeem) {
            return ['valid' => false, 'message' => 'Insufficient points balance.'];
        }

        $discountAmount = round($pointsToRedeem * $config['points_to_dollar_rate'], 2);

        return [
            'valid' => true,
            'points' => $pointsToRedeem,
            'discount_amount' => $discountAmount,
            'new_balance' => $customer->loyalty_points_balance - $pointsToRedeem,
        ];
    }

    /**
     * Redeem points — deduct balance and create ledger entry.
     */
    public function redeemPoints(int $customerId, int $storeId, int $points, string $description = 'Redeemed at checkout'): LoyaltyPoint
    {
        return DB::transaction(function () use ($customerId, $storeId, $points, $description) {
            $customer = Customer::withoutGlobalScope('store')->find($customerId);

            $newBalance = max(0, $customer->loyalty_points_balance - $points);
            $customer->update(['loyalty_points_balance' => $newBalance]);

            return LoyaltyPoint::create([
                'store_id' => $storeId,
                'customer_id' => $customerId,
                'points' => -$points,
                'type' => 'redeemed',
                'description' => $description,
                'balance_after' => $newBalance,
            ]);
        });
    }

    /**
     * Admin: Manually adjust customer points.
     */
    public function adjustPoints(int $customerId, int $storeId, int $points, string $reason): LoyaltyPoint
    {
        return DB::transaction(function () use ($customerId, $storeId, $points, $reason) {
            $customer = Customer::withoutGlobalScope('store')->find($customerId);

            $newBalance = max(0, $customer->loyalty_points_balance + $points);
            $customer->update(['loyalty_points_balance' => $newBalance]);

            return LoyaltyPoint::create([
                'store_id' => $storeId,
                'customer_id' => $customerId,
                'points' => $points,
                'type' => 'adjusted',
                'description' => $reason,
                'balance_after' => $newBalance,
            ]);
        });
    }

    /**
     * Get customer's point history.
     */
    public function getHistory(int $customerId, int $storeId, int $perPage = 20)
    {
        return LoyaltyPoint::withoutGlobalScope('store')
            ->where('store_id', $storeId)
            ->where('customer_id', $customerId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
