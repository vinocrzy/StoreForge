<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class CurrencyService
{
    /**
     * Get multi-currency configuration for a store.
     */
    public function getConfig(int $storeId): array
    {
        $cacheKey = "currency:config:{$storeId}";

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($storeId) {
            $settings = DB::table('store_settings')
                ->where('store_id', $storeId)
                ->whereIn('key', ['base_currency', 'enabled_currencies', 'exchange_rates'])
                ->pluck('value', 'key')
                ->toArray();

            return [
                'base_currency' => $settings['base_currency'] ?? 'USD',
                'enabled_currencies' => json_decode($settings['enabled_currencies'] ?? '["USD"]', true),
                'exchange_rates' => json_decode($settings['exchange_rates'] ?? '{}', true),
            ];
        });
    }

    /**
     * Convert a price from base currency to target currency.
     */
    public function convert(float $amount, string $toCurrency, int $storeId): float
    {
        $config = $this->getConfig($storeId);

        if ($toCurrency === $config['base_currency']) {
            return $amount;
        }

        $rate = $config['exchange_rates'][$toCurrency] ?? null;

        if (!$rate) {
            return $amount;
        }

        return round($amount * $rate, 2);
    }

    /**
     * Format a price in a given currency.
     */
    public function format(float $amount, string $currency): string
    {
        $symbols = [
            'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'INR' => '₹',
            'JPY' => '¥', 'CAD' => 'CA$', 'AUD' => 'A$', 'SGD' => 'S$',
        ];

        $symbol = $symbols[$currency] ?? $currency . ' ';
        $decimals = in_array($currency, ['JPY']) ? 0 : 2;

        return $symbol . number_format($amount, $decimals);
    }

    /**
     * Update exchange rates for a store.
     */
    public function updateRates(int $storeId, string $baseCurrency, array $rates): void
    {
        $this->upsertSetting($storeId, 'base_currency', $baseCurrency);
        $this->upsertSetting($storeId, 'exchange_rates', json_encode($rates));
        $this->upsertSetting($storeId, 'enabled_currencies', json_encode(array_merge([$baseCurrency], array_keys($rates))));

        Cache::forget("currency:config:{$storeId}");
    }

    private function upsertSetting(int $storeId, string $key, string $value): void
    {
        DB::table('store_settings')->updateOrInsert(
            ['store_id' => $storeId, 'key' => $key],
            ['value' => $value, 'updated_at' => now()]
        );
    }
}
