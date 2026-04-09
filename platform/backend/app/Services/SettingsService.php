<?php

namespace App\Services;

use App\Models\StoreSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class SettingsService
{
    /**
     * Default settings grouped by category.
     * These are returned when no overrides exist in the database.
     */
    private const DEFAULTS = [
        'general' => [
            'store_name'        => ['value' => '', 'type' => 'string', 'description' => 'Display name of the store', 'is_public' => true],
            'store_description' => ['value' => '', 'type' => 'string', 'description' => 'Short store description', 'is_public' => true],
            'store_email'       => ['value' => '', 'type' => 'string', 'description' => 'Contact email'],
            'store_phone'       => ['value' => '', 'type' => 'string', 'description' => 'Contact phone', 'is_public' => true],
            'store_address'     => ['value' => '', 'type' => 'string', 'description' => 'Physical address', 'is_public' => true],
            'currency'          => ['value' => 'USD', 'type' => 'string', 'description' => 'Default currency code', 'is_public' => true],
            'timezone'          => ['value' => 'UTC', 'type' => 'string', 'description' => 'Store timezone'],
            'logo_url'          => ['value' => '', 'type' => 'string', 'description' => 'Logo image URL', 'is_public' => true],
            'favicon_url'       => ['value' => '', 'type' => 'string', 'description' => 'Favicon URL', 'is_public' => true],
        ],
        'branding' => [
            'primary_color'   => ['value' => '#3C50E0', 'type' => 'string', 'description' => 'Primary brand color', 'is_public' => true],
            'secondary_color' => ['value' => '#64748B', 'type' => 'string', 'description' => 'Secondary brand color', 'is_public' => true],
            'accent_color'    => ['value' => '#10B981', 'type' => 'string', 'description' => 'Accent color', 'is_public' => true],
            'font_family'     => ['value' => 'Inter', 'type' => 'string', 'description' => 'Primary font', 'is_public' => true],
        ],
        'policies' => [
            'return_policy'  => ['value' => '', 'type' => 'string', 'description' => 'Return policy text', 'is_public' => true],
            'privacy_policy' => ['value' => '', 'type' => 'string', 'description' => 'Privacy policy text', 'is_public' => true],
            'terms_of_service' => ['value' => '', 'type' => 'string', 'description' => 'Terms of service text', 'is_public' => true],
        ],
        'checkout' => [
            'allow_guest_checkout' => ['value' => '1', 'type' => 'boolean', 'description' => 'Allow checkout without account', 'is_public' => true],
            'require_phone'        => ['value' => '1', 'type' => 'boolean', 'description' => 'Require phone number at checkout', 'is_public' => true],
            'require_account'      => ['value' => '0', 'type' => 'boolean', 'description' => 'Require account creation'],
            'min_order_amount'     => ['value' => '0', 'type' => 'integer', 'description' => 'Minimum order amount', 'is_public' => true],
        ],
        'payments' => [
            'manual_payment_enabled'      => ['value' => '1', 'type' => 'boolean', 'description' => 'Enable manual payments'],
            'manual_payment_instructions' => ['value' => '', 'type' => 'string', 'description' => 'Instructions for manual payment', 'is_public' => true],
            'cod_enabled'                 => ['value' => '0', 'type' => 'boolean', 'description' => 'Cash on delivery enabled', 'is_public' => true],
        ],
        'shipping' => [
            'free_shipping_threshold' => ['value' => '0', 'type' => 'integer', 'description' => 'Order amount for free shipping', 'is_public' => true],
            'flat_rate_enabled'       => ['value' => '0', 'type' => 'boolean', 'description' => 'Enable flat rate shipping', 'is_public' => true],
            'flat_rate_cost'          => ['value' => '0', 'type' => 'integer', 'description' => 'Flat rate shipping cost', 'is_public' => true],
        ],
        'seo' => [
            'meta_title'       => ['value' => '', 'type' => 'string', 'description' => 'Default meta title', 'is_public' => true],
            'meta_description' => ['value' => '', 'type' => 'string', 'description' => 'Default meta description', 'is_public' => true],
            'meta_keywords'    => ['value' => '', 'type' => 'string', 'description' => 'Default meta keywords', 'is_public' => true],
            'google_analytics' => ['value' => '', 'type' => 'string', 'description' => 'Google Analytics ID'],
            'facebook_pixel'   => ['value' => '', 'type' => 'string', 'description' => 'Facebook Pixel ID'],
        ],
        'notifications' => [
            'order_confirmation_email' => ['value' => '1', 'type' => 'boolean', 'description' => 'Send order confirmation emails'],
            'order_shipped_email'      => ['value' => '1', 'type' => 'boolean', 'description' => 'Send shipment notification emails'],
            'low_stock_email'          => ['value' => '1', 'type' => 'boolean', 'description' => 'Send low stock alerts'],
            'low_stock_threshold'      => ['value' => '10', 'type' => 'integer', 'description' => 'Default low stock threshold'],
            'admin_email'              => ['value' => '', 'type' => 'string', 'description' => 'Admin notification email'],
        ],
        'security' => [
            'session_timeout_minutes' => ['value' => '60', 'type' => 'integer', 'description' => 'Session timeout in minutes'],
            'max_login_attempts'      => ['value' => '5', 'type' => 'integer', 'description' => 'Max failed login attempts'],
        ],
    ];

    /**
     * Get all settings for the current tenant, grouped by category.
     */
    public function getAll(): array
    {
        $saved = StoreSetting::all()->groupBy('group');
        $result = [];

        foreach (self::DEFAULTS as $group => $defaults) {
            $result[$group] = $this->mergeGroupSettings($group, $defaults, $saved->get($group, collect()));
        }

        return $result;
    }

    /**
     * Get settings for a single group.
     */
    public function getGroup(string $group): array
    {
        $defaults = self::DEFAULTS[$group] ?? [];
        $saved = StoreSetting::group($group)->get();

        return $this->mergeGroupSettings($group, $defaults, $saved);
    }

    /**
     * Update multiple settings at once.
     * Input structure: ['group.key' => value, ...] or ['group' => ['key' => value, ...], ...]
     */
    public function updateMany(array $data): void
    {
        foreach ($data as $group => $keys) {
            if (!is_array($keys)) {
                continue;
            }

            $defaults = self::DEFAULTS[$group] ?? [];

            foreach ($keys as $key => $value) {
                $meta = $defaults[$key] ?? ['type' => 'string', 'is_public' => false];
                $type = $meta['type'] ?? 'string';

                // Normalize value to string for storage
                $storedValue = match ($type) {
                    'boolean' => ($value === true || $value === 'true' || $value === '1') ? '1' : '0',
                    'json'    => is_string($value) ? $value : json_encode($value),
                    'integer' => (string) (int) $value,
                    default   => (string) ($value ?? ''),
                };

                StoreSetting::updateOrCreate(
                    ['store_id' => tenant()->id, 'group' => $group, 'key' => $key],
                    [
                        'value'       => $storedValue,
                        'type'        => $type,
                        'description' => $meta['description'] ?? null,
                        'is_public'   => $meta['is_public'] ?? false,
                    ]
                );
            }
        }
    }

    /**
     * Get a single setting value by group and key.
     */
    public function get(string $group, string $key, mixed $default = null): mixed
    {
        $setting = StoreSetting::where('group', $group)->where('key', $key)->first();

        if ($setting) {
            return $setting->typed_value;
        }

        return $this->DEFAULTS[$group][$key]['value'] ?? $default;
    }

    // -------------------------------------------------------------------------
    private function mergeGroupSettings(string $group, array $defaults, Collection $saved): array
    {
        $savedMap = $saved->keyBy('key');
        $result = [];

        foreach ($defaults as $key => $meta) {
            $savedRow = $savedMap->get($key);
            $result[$key] = [
                'value'       => $savedRow ? $savedRow->typed_value : $this->castDefault($meta['value'], $meta['type'] ?? 'string'),
                'type'        => $meta['type'] ?? 'string',
                'description' => $meta['description'] ?? null,
                'is_public'   => $meta['is_public'] ?? false,
            ];
        }

        return $result;
    }

    private function castDefault(mixed $value, string $type): mixed
    {
        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json'    => is_string($value) ? json_decode($value, true) : $value,
            default   => $value,
        };
    }
}
