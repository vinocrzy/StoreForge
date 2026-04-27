<?php

namespace App\Services;

use App\Models\ShippingMethod;
use Illuminate\Database\Eloquent\Collection;

class ShippingService
{
    /**
     * Return all active shipping methods applicable to the given cart total.
     *
     * @param  float  $cartTotal
     * @param  int    $storeId
     * @return Collection<ShippingMethod>
     */
    public function getAvailableMethods(float $cartTotal, int $storeId): Collection
    {
        return ShippingMethod::withoutGlobalScope('store')
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get()
            ->map(function (ShippingMethod $method) use ($cartTotal) {
                $method->calculated_rate = $this->calculateRate($method, $cartTotal);
                return $method;
            });
    }

    /**
     * Calculate the shipping cost for a single method.
     *
     * @param  ShippingMethod  $method
     * @param  float           $cartTotal
     * @param  float           $cartWeight  Total weight of items in kg (used for weight_based)
     * @return float
     */
    public function calculateRate(ShippingMethod $method, float $cartTotal, float $cartWeight = 0.0): float
    {
        return match ($method->type) {
            'flat_rate'     => (float) ($method->rate ?? 0),
            'weight_based'  => round((float) ($method->rate ?? 0) * $cartWeight, 2),
            'free_above'    => $cartTotal >= (float) ($method->free_above ?? 0) ? 0.0 : (float) ($method->rate ?? 0),
            'local_pickup'  => 0.0,
            default         => 0.0,
        };
    }
}
