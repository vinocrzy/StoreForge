<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\WarehouseRequest;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @group Warehouses
 * 
 * Manage warehouses for the authenticated store. All operations are automatically scoped to the current tenant.
 * 
 * @authenticated
 */
class WarehouseController extends Controller
{
    /**
     * List warehouses
     * 
     * Get a paginated list of warehouses with optional filtering.
     * Warehouses are automatically scoped to the authenticated store.
     * 
     * @queryParam is_active boolean Filter by active status. Example: 1
     * @queryParam sort_by string Sort field. Example: name
     * @queryParam sort_order string Sort direction: asc, desc. Example: asc
     * @queryParam per_page integer Items per page (max 100). Example: 20
     * 
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Main Warehouse",
     *       "code": "WH-001",
     *       "address": "123 Storage St",
     *       "city": "New York",
     *       "state": "NY",
     *       "postal_code": "10001",
     *       "country": "US",
     *       "is_active": true,
     *       "created_at": "2026-04-06T10:00:00Z"
     *     }
     *   ],
     *   "meta": {
     *     "current_page": 1,
     *     "per_page": 20,
     *     "total": 2
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $query = Warehouse::query();

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', (bool) $request->is_active);
        }

        // Sort
        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = min($request->input('per_page', 20), 100);
        $warehouses = $query->paginate($perPage);

        return response()->json($warehouses);
    }

    /**
     * Get warehouse details
     * 
     * Retrieve a single warehouse with inventory count.
     * 
     * @urlParam id integer required Warehouse ID. Example: 1
     * 
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": 1,
     *     "name": "Main Warehouse",
     *     "code": "WH-001",
     *     "address": "123 Storage St",
     *     "city": "New York",
     *     "state": "NY",
     *     "postal_code": "10001",
     *     "country": "US",
     *     "is_active": true,
     *     "created_at": "2026-04-06T10:00:00Z",
     *     "inventory_count": 90
     *   }
     * }
     * 
     * @response 404 scenario="Not found" {
     *   "message": "Warehouse not found"
     * }
     */
    public function show(int $id): JsonResponse
    {
        $warehouse = Warehouse::withCount('inventories')->findOrFail($id);

        return response()->json(['data' => $warehouse]);
    }

    /**
     * Create warehouse
     * 
     * Create a new warehouse for the authenticated store.
     * 
     * @bodyParam name string required Warehouse name. Example: Main Warehouse
     * @bodyParam code string required Warehouse code (unique per store). Example: WH-001
     * @bodyParam address string Warehouse address. Example: 123 Storage St
     * @bodyParam city string City. Example: New York
     * @bodyParam state string State/Province. Example: NY
     * @bodyParam postal_code string Postal code. Example: 10001
     * @bodyParam country string Country code (ISO 3166-1 alpha-2). Example: US
     * @bodyParam is__active boolean Warehouse active status. Example: true
     * 
     * @response 201 scenario="Created" {
     *   "data": {
     *     "id": 1,
     *     "name": "Main Warehouse",
     *     "code": "WH-001",
     *     "address": "123 Storage St",
     *     "city": "New York",
     *     "is_active": true,
     *     "created_at": "2026-04-06T10:00:00Z"
     *   }
     * }
     * 
     * @response 422 scenario="Validation failed" {
     *   "message": "The given data was invalid",
     *   "errors": {
     *     "code": ["Warehouse code is required"]
     *   }
     * }
     */
    public function store(WarehouseRequest $request): JsonResponse
    {
        $payload = $request->validated();

        if (!Warehouse::query()->exists()) {
            $payload['is_default'] = true;
        }

        $warehouse = Warehouse::create($payload);

        if (!empty($payload['is_default'])) {
            Warehouse::where('id', '!=', $warehouse->id)->update(['is_default' => false]);
            $warehouse->refresh();
        }

        return response()->json(['data' => $warehouse], 201);
    }

    /**
     * Update warehouse
     * 
     * Update an existing warehouse. All fields are optional for updates.
     * 
     * @urlParam id integer required Warehouse ID. Example: 1
     * 
     * @bodyParam name string Warehouse name. Example: Main Warehouse Updated
     * @bodyParam code string Warehouse code. Example: WH-001
     * @bodyParam address string Warehouse address. Example: 123 Storage St
     * @bodyParam city string City. Example: New York
     * @bodyParam state string State/Province. Example: NY
     * @bodyParam postal_code string Postal code. Example: 10001
     * @bodyParam country string Country code (ISO 3166-1 alpha-2). Example: US
     * @bodyParam is_active boolean Warehouse active status. Example: false
     * 
     * @response 200 scenario="Updated" {
     *   "data": {
     *     "id": 1,
     *     "name": "Main Warehouse Updated",
     *     "code": "WH-001",
     *     "is_active": false,
     *     "updated_at": "2026-04-06T11:00:00Z"
     *   }
     * }
     * 
     * @response 404 scenario="Not found" {
     *   "message": "Warehouse not found"
     * }
     */
    public function update(WarehouseRequest $request, int $id): JsonResponse
    {
        $warehouse = Warehouse::findOrFail($id);

        DB::transaction(function () use ($warehouse, $request) {
            $payload = $request->validated();
            $warehouse->update($payload);

            if (!empty($payload['is_default'])) {
                Warehouse::where('id', '!=', $warehouse->id)->update(['is_default' => false]);
                $warehouse->refresh();
            }
        });

        return response()->json(['data' => $warehouse]);
    }

    /**
     * Set default warehouse
     *
     * Mark a warehouse as the default warehouse for the current store.
     * Any previously default warehouse will be unset.
     *
     * @urlParam id integer required Warehouse ID. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "message": "Default warehouse updated successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "Main Warehouse",
     *     "is_default": true
     *   }
     * }
     */
    public function setDefault(int $id): JsonResponse
    {
        $warehouse = Warehouse::findOrFail($id);

        DB::transaction(function () use ($warehouse) {
            Warehouse::query()->update(['is_default' => false]);
            $warehouse->update(['is_default' => true]);
            $warehouse->refresh();
        });

        return response()->json([
            'message' => 'Default warehouse updated successfully',
            'data' => $warehouse,
        ]);
    }

    /**
     * Delete warehouse
     * 
     * Soft delete a warehouse. Inventory records will have warehouse_id set to null.
     * 
     * @urlParam id integer required Warehouse ID. Example: 1
     * 
     * @response 200 scenario="Deleted" {
     *   "message": "Warehouse deleted successfully"
     * }
     * 
     * @response 404 scenario="Not found" {
     *   "message": "Warehouse not found"
     * }
     */
    public function destroy(int $id): JsonResponse
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->delete();

        return response()->json(['message' => 'Warehouse deleted successfully']);
    }
}
