<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\CurrencyService;
use App\Services\EmailMarketingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @group Store Settings — Phase 9C
 *
 * Endpoints for currency and email marketing settings.
 */
class StoreSettingsExtController extends Controller
{
    public function __construct(
        private readonly CurrencyService $currencyService,
        private readonly EmailMarketingService $emailMarketingService,
    ) {}

    // -----------------------------------------------------------------------
    // Currency
    // -----------------------------------------------------------------------

    /**
     * Get currency settings
     *
     * @response 200 {"data": {"base_currency": "USD", "enabled_currencies": ["USD", "INR"], "exchange_rates": {"INR": 83.5}}}
     */
    public function getCurrencySettings(): JsonResponse
    {
        return response()->json(['data' => $this->currencyService->getConfig(tenant()->id)]);
    }

    /**
     * Update currency settings
     *
     * @bodyParam base_currency string required ISO 4217 currency code. Example: USD
     * @bodyParam exchange_rates object required Map of currency code to exchange rate. Example: {"INR": 83.5, "EUR": 0.92}
     */
    public function updateCurrencySettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'base_currency' => 'required|string|size:3',
            'exchange_rates' => 'required|array',
            'exchange_rates.*' => 'numeric|min:0',
        ]);

        $this->currencyService->updateRates(
            tenant()->id,
            strtoupper($validated['base_currency']),
            $validated['exchange_rates']
        );

        return response()->json([
            'message' => 'Currency settings updated.',
            'data' => $this->currencyService->getConfig(tenant()->id),
        ]);
    }

    // -----------------------------------------------------------------------
    // Email Marketing
    // -----------------------------------------------------------------------

    /**
     * Get email marketing settings
     *
     * @response 200 {"data": {"enabled": true, "api_key_set": true, "list_id": "abc123", "subscriber_count": 42}}
     */
    public function getEmailMarketingSettings(): JsonResponse
    {
        $storeId = tenant()->id;
        $config = $this->emailMarketingService->getMailchimpConfig($storeId);

        return response()->json([
            'data' => [
                'enabled' => $config['enabled'],
                'api_key_set' => !empty($config['api_key']),
                'list_id' => $config['list_id'],
                'subscriber_count' => $this->emailMarketingService->getSubscriberCount($storeId),
            ],
        ]);
    }

    /**
     * Update email marketing settings (Mailchimp)
     *
     * @bodyParam enabled boolean Whether email marketing is enabled. Example: true
     * @bodyParam mailchimp_api_key string Mailchimp API key. Example: abc123-us1
     * @bodyParam mailchimp_list_id string Mailchimp Audience List ID. Example: def456
     */
    public function updateEmailMarketingSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'enabled' => 'sometimes|boolean',
            'mailchimp_api_key' => 'sometimes|nullable|string|max:255',
            'mailchimp_list_id' => 'sometimes|nullable|string|max:100',
        ]);

        $storeId = tenant()->id;
        $map = [
            'enabled' => 'email_marketing_enabled',
            'mailchimp_api_key' => 'mailchimp_api_key',
            'mailchimp_list_id' => 'mailchimp_list_id',
        ];

        foreach ($validated as $key => $value) {
            \DB::table('store_settings')->updateOrInsert(
                ['store_id' => $storeId, 'key' => $map[$key]],
                ['value' => is_bool($value) ? ($value ? 'true' : 'false') : (string) $value, 'updated_at' => now()]
            );
        }

        return response()->json(['message' => 'Email marketing settings updated.']);
    }
}
