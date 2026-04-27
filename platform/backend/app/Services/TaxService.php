<?php

namespace App\Services;

use App\Models\StoreSetting;
use Illuminate\Support\Facades\Cache;

class TaxService
{
    /**
     * Calculate tax amount for the given subtotal.
     *
     * Reads tax configuration from store_settings:
     *   - tax.tax_enabled  (bool)
     *   - tax.tax_rate     (float, percentage)
     *   - tax.tax_display  ('inclusive' | 'exclusive')
     *   - tax.category_tax_rates (JSON map: category_id => rate)
     *
     * @param  float    $subtotal
     * @param  int      $storeId
     * @param  int|null $categoryId  Optional — use category-specific rate when available
     * @return float
     */
    public function calculate(float $subtotal, int $storeId, ?int $categoryId = null): float
    {
        $config = $this->getTaxConfig($storeId);

        if (empty($config['tax_enabled'])) {
            return 0.0;
        }

        $rate = $this->resolveRate($config, $categoryId);

        if ($rate <= 0) {
            return 0.0;
        }

        $display = $config['tax_display'] ?? 'exclusive';

        if ($display === 'inclusive') {
            // Extract tax from the subtotal (which already includes tax)
            return round($subtotal - ($subtotal / (1 + $rate / 100)), 2);
        }

        // Exclusive: tax is added on top of the subtotal
        return round($subtotal * ($rate / 100), 2);
    }

    /**
     * Return current tax configuration for a store as a simple associative array.
     */
    public function getTaxConfig(int $storeId): array
    {
        return Cache::remember("tax_config:{$storeId}", 300, function () use ($storeId) {
            $settings = StoreSetting::withoutGlobalScope('store')
                ->where('store_id', $storeId)
                ->where('group', 'tax')
                ->get()
                ->pluck('value', 'key')
                ->toArray();

            return [
                'tax_enabled'         => filter_var($settings['tax_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'tax_rate'            => (float) ($settings['tax_rate'] ?? 0),
                'tax_display'         => $settings['tax_display'] ?? 'exclusive',
                'tax_label'           => $settings['tax_label'] ?? 'Tax',
                'category_tax_rates'  => json_decode($settings['category_tax_rates'] ?? '{}', true) ?: [],
            ];
        });
    }

    /**
     * Persist tax configuration for the current tenant store.
     */
    public function updateTaxConfig(int $storeId, array $data): void
    {
        $fields = ['tax_enabled', 'tax_rate', 'tax_display', 'tax_label', 'category_tax_rates'];

        foreach ($fields as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }

            $value = $key === 'category_tax_rates'
                ? json_encode($data[$key])
                : (string) $data[$key];

            StoreSetting::withoutGlobalScope('store')->updateOrCreate(
                ['store_id' => $storeId, 'group' => 'tax', 'key' => $key],
                ['value' => $value, 'type' => 'string', 'is_public' => false]
            );
        }

        Cache::forget("tax_config:{$storeId}");
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function resolveRate(array $config, ?int $categoryId): float
    {
        if ($categoryId !== null && !empty($config['category_tax_rates'][$categoryId])) {
            return (float) $config['category_tax_rates'][$categoryId];
        }

        return (float) ($config['tax_rate'] ?? 0);
    }
}
