<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCreateRequest;
use App\Http\Requests\StoreStatusRequest;
use App\Services\StoreProvisioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Stores
 *
 * Super Admin APIs for cross-tenant store management and provisioning.
 *
 * @authenticated
 */
class StoreController extends Controller
{
    public function __construct(
        private StoreProvisioningService $storeProvisioningService
    ) {
    }

    /**
     * List stores
     *
     * Get paginated list of stores. Super Admin only.
     *
     * @queryParam search string Search by name, slug, or domain. Example: demo
     * @queryParam status string Filter by status: active, inactive, suspended. Example: active
     * @queryParam sort_by string Sort field. Example: created_at
     * @queryParam sort_order string Sort direction: asc, desc. Example: desc
     * @queryParam per_page integer Items per page (max 100). Example: 20
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Demo Store",
     *       "slug": "demo-store",
     *       "status": "active"
     *     }
     *   ]
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        $filters = $request->only(['search', 'status', 'sort_by', 'sort_order']);
        $perPage = min((int) $request->input('per_page', 20), 100);

        $stores = $this->storeProvisioningService->getStores($filters, $perPage);

        return response()->json($stores);
    }

    /**
     * Create store
     *
     * Create a new store with owner account. Super Admin only.
     *
     * @bodyParam name string required Store name. Example: Honey Bee Store
     * @bodyParam slug string required Unique store slug. Example: honey-bee
     * @bodyParam domain string Store domain. Example: honey-bee.demo.localhost
     * @bodyParam status string Store status: active, inactive, suspended. Example: active
     * @bodyParam email string Store contact email. Example: contact@honeybee.com
     * @bodyParam phone string Store contact phone in E.164 format. Example: +12025550111
     * @bodyParam currency string 3-letter currency code. Example: USD
     * @bodyParam timezone string Store timezone. Example: America/New_York
     * @bodyParam language string 2-letter language code. Example: en
    * @bodyParam admin_name string required Store admin name. Example: Honey Admin
    * @bodyParam admin_phone string required Store admin phone in E.164 format. Example: +12025550112
    * @bodyParam admin_email string Store admin email (optional). Example: admin@honeybee.com
    * @bodyParam admin_password string required Store admin password (min 8). Example: SecurePass123
     *
     * @response 201 {
     *   "data": {
     *     "store": {"id": 2, "slug": "honey-bee"},
    *     "admin": {"id": 10, "email": "admin@honeybee.com"}
     *   }
     * }
     */
    public function store(StoreCreateRequest $request): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        $result = $this->storeProvisioningService->createStore($request->validated());

        return response()->json(['data' => $result], 201);
    }

    /**
     * Get store details
     *
     * Get a single store with users and summary metrics. Super Admin only.
     *
     * @urlParam id integer required Store ID. Example: 1
     *
     * @response 200 {
     *   "data": {"id": 1, "name": "Demo Store"},
     *   "meta": {"products_count": 0, "customers_count": 0, "orders_count": 0}
     * }
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        $store = $this->storeProvisioningService->getStore($id);

        $meta = [
            'products_count' => \App\Models\Product::query()->where('store_id', $store->id)->count(),
            'customers_count' => \App\Models\Customer::query()->where('store_id', $store->id)->count(),
            'orders_count' => \App\Models\Order::query()->where('store_id', $store->id)->count(),
        ];

        return response()->json([
            'data' => $store,
            'meta' => $meta,
        ]);
    }

    /**
     * Update store status
     *
     * Activate, deactivate, or suspend a store. Super Admin only.
     *
     * @urlParam id integer required Store ID. Example: 1
     * @bodyParam status string required New status: active, inactive, suspended. Example: inactive
     *
     * @response 200 {
     *   "data": {"id": 1, "status": "inactive"}
     * }
     */
    public function updateStatus(StoreStatusRequest $request, int $id): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        $store = $this->storeProvisioningService->updateStatus($id, $request->validated('status'));

        return response()->json(['data' => $store]);
    }

    private function ensureSuperAdmin(Request $request): void
    {
        $user = $request->user();

        abort_unless($user && $user->hasRole('super-admin'), 403, 'Only super admin can manage stores.');
    }
}
