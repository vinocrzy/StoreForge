<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Store Settings
 *
 * Manage store configuration and preferences.
 *
 * @authenticated
 */
class SettingsController extends Controller
{
    public function __construct(private readonly SettingsService $settingsService) {}

    /**
     * Get all settings
     *
     * Retrieve all store settings grouped by category (general, branding, policies, checkout, payments, shipping, seo, notifications, security).
     *
     * @response 200 {
     *   "data": {
     *     "general": {
     *       "store_name": { "value": "My Store", "type": "string", "description": "Display name of the store", "is_public": true },
     *       "currency": { "value": "USD", "type": "string", "description": "Default currency code", "is_public": true }
     *     },
     *     "seo": {
     *       "meta_title": { "value": "", "type": "string", "description": "Default meta title", "is_public": true }
     *     }
     *   }
     * }
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->settingsService->getAll(),
        ]);
    }

    /**
     * Get settings by group
     *
     * Retrieve settings for a specific group.
     *
     * @urlParam group string required Settings group name. Allowed: general, branding, policies, checkout, payments, shipping, seo, notifications, security. Example: general
     *
     * @response 200 {
     *   "data": {
     *     "store_name": { "value": "My Store", "type": "string", "description": "Display name of the store", "is_public": true }
     *   }
     * }
     * @response 404 { "message": "Settings group not found." }
     */
    public function show(string $group): JsonResponse
    {
        $allowed = ['general', 'branding', 'policies', 'checkout', 'payments', 'shipping', 'seo', 'notifications', 'security'];

        if (!in_array($group, $allowed, true)) {
            return response()->json(['message' => 'Settings group not found.'], 404);
        }

        return response()->json([
            'data' => $this->settingsService->getGroup($group),
        ]);
    }

    /**
     * Update settings
     *
     * Update one or more settings. Send a nested object keyed by group and setting key.
     *
     * @bodyParam general object optional General settings to update.
     * @bodyParam general.store_name string optional Store display name. Example: My Awesome Store
     * @bodyParam general.currency string optional ISO currency code. Example: USD
     * @bodyParam branding object optional Branding settings: primary_color, secondary_color, accent_color, font_family.
     * @bodyParam seo object optional SEO settings: meta_title, meta_description, meta_keywords, google_analytics.
     * @bodyParam checkout object optional Checkout settings: allow_guest_checkout, require_phone.
     * @bodyParam notifications object optional Notification settings: low_stock_threshold, admin_email.
     *
     * @response 200 {
     *   "message": "Settings updated successfully",
     *   "data": {}
     * }
     */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'general'           => 'sometimes|array',
            'branding'          => 'sometimes|array',
            'policies'          => 'sometimes|array',
            'checkout'          => 'sometimes|array',
            'payments'          => 'sometimes|array',
            'shipping'          => 'sometimes|array',
            'seo'               => 'sometimes|array',
            'notifications'     => 'sometimes|array',
            'security'          => 'sometimes|array',
        ]);

        $this->settingsService->updateMany($data);

        return response()->json([
            'message' => 'Settings updated successfully',
            'data'    => $this->settingsService->getAll(),
        ]);
    }
}
