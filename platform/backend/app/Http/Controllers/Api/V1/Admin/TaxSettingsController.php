<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateTaxSettingsRequest;
use App\Services\TaxService;
use Illuminate\Http\JsonResponse;

/**
 * @group Tax Settings (Admin)
 *
 * Manage the store's tax calculation configuration.
 *
 * @authenticated
 */
class TaxSettingsController extends Controller
{
    public function __construct(private TaxService $taxService) {}

    /**
     * Get tax settings
     *
     * Returns the current tax configuration for the store.
     *
     * @response 200 {
     *   "data": {
     *     "tax_enabled": true,
     *     "tax_rate": 10.0,
     *     "tax_display": "exclusive",
     *     "tax_label": "GST",
     *     "category_tax_rates": {"3": 5.0}
     *   }
     * }
     */
    public function show(): JsonResponse
    {
        $config = $this->taxService->getTaxConfig(tenant()->id);

        return response()->json(['data' => $config]);
    }

    /**
     * Update tax settings
     *
     * Update the store's tax configuration.
     *
     * @bodyParam tax_enabled boolean Enable/disable tax calculation. Example: true
     * @bodyParam tax_rate number Tax rate as a percentage (0–100). Example: 10.0
     * @bodyParam tax_display string Tax display mode: inclusive or exclusive. Example: exclusive
     * @bodyParam tax_label string Label shown to customers. Example: GST
     * @bodyParam category_tax_rates object Map of category_id to rate override. Example: {"3": 5.0}
     *
     * @response 200 {"data": {"tax_enabled": true, "tax_rate": 10.0}, "message": "Tax settings updated."}
     */
    public function update(UpdateTaxSettingsRequest $request): JsonResponse
    {
        $storeId = tenant()->id;

        $this->taxService->updateTaxConfig($storeId, $request->validated());

        return response()->json([
            'data'    => $this->taxService->getTaxConfig($storeId),
            'message' => 'Tax settings updated.',
        ]);
    }
}
