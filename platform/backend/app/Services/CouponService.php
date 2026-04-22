<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;
use Illuminate\Support\Facades\DB;

class CouponService
{
    /**
     * Validate a coupon code against a cart.
     *
     * @return array{valid: bool, discount: float, error: string|null}
     */
    public function validate(string $code, float $cartSubtotal, ?int $customerId = null): array
    {
        $code = strtoupper(trim($code));

        // 1. Coupon exists and belongs to current store (global scope handles tenant)
        $coupon = Coupon::withoutGlobalScope('store')
            ->where('store_id', tenant()->id)
            ->where('code', $code)
            ->first();

        if (!$coupon) {
            return ['valid' => false, 'discount' => 0, 'error' => 'Invalid coupon code'];
        }

        // 2. Status check
        if ($coupon->status !== 'active') {
            return ['valid' => false, 'discount' => 0, 'error' => 'This coupon is no longer active'];
        }

        // 3. Date range check
        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            return ['valid' => false, 'discount' => 0, 'error' => 'This coupon is not yet active'];
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            return ['valid' => false, 'discount' => 0, 'error' => 'This coupon has expired'];
        }

        // 4. Usage limit check
        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            return ['valid' => false, 'discount' => 0, 'error' => 'This coupon has reached its usage limit'];
        }

        // 5. Per-customer usage limit check
        if ($customerId !== null && $coupon->usage_limit_per_customer !== null) {
            $customerUsageCount = CouponUsage::where('coupon_id', $coupon->id)
                ->where('customer_id', $customerId)
                ->count();

            if ($customerUsageCount >= $coupon->usage_limit_per_customer) {
                return ['valid' => false, 'discount' => 0, 'error' => 'You have already used this coupon'];
            }
        }

        // 6. Minimum purchase check
        if ($coupon->minimum_purchase_amount !== null && $cartSubtotal < (float) $coupon->minimum_purchase_amount) {
            $min = number_format((float) $coupon->minimum_purchase_amount, 2);
            return ['valid' => false, 'discount' => 0, 'error' => "Minimum purchase of \${$min} required for this coupon"];
        }

        // All checks passed — calculate discount
        $discount = $this->calculateDiscount($coupon, $cartSubtotal);

        return ['valid' => true, 'discount' => $discount, 'error' => null];
    }

    /**
     * Calculate the discount amount for a valid coupon.
     */
    public function calculateDiscount(Coupon $coupon, float $cartSubtotal): float
    {
        if ($coupon->type === 'percentage') {
            $discount = $cartSubtotal * ((float) $coupon->value / 100);

            // Cap at maximum_discount_amount if set
            if ($coupon->maximum_discount_amount !== null && $discount > (float) $coupon->maximum_discount_amount) {
                $discount = (float) $coupon->maximum_discount_amount;
            }

            return round($discount, 2);
        }

        // Fixed discount — never exceed cart total
        return round(min((float) $coupon->value, $cartSubtotal), 2);
    }

    /**
     * Record coupon usage when order is placed.
     * Increments used_count on the coupon.
     */
    public function recordUsage(Coupon $coupon, int $orderId, int $customerId, float $discountAmount): CouponUsage
    {
        return DB::transaction(function () use ($coupon, $orderId, $customerId, $discountAmount) {
            $usage = CouponUsage::create([
                'coupon_id' => $coupon->id,
                'order_id' => $orderId,
                'customer_id' => $customerId,
                'discount_amount' => $discountAmount,
            ]);

            $coupon->increment('used_count');

            return $usage;
        });
    }
}
