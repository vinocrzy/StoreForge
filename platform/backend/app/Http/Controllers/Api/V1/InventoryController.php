<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\InventoryRequest;
use App\Http\Requests\StockAdjustmentRequest;
use App\Services\InventoryService;
use App\Models\Inventory;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @group Inventory
 * 
 * Manage inventory and stock levels for the authenticated store. All operations are automatically scoped to the current tenant.
 * Supports multi-warehouse inventory tracking, stock reservations, and stock movements.
 * 
 * @authenticated
 */
class InventoryController extends Controller
{
    public function __construct(
        private InventoryService $inventoryService
    ) {}

    /**
     * List inventory
     * 
     * Get a paginated list of inventory records with optional filtering.
     * Inventory is automatically scoped to the authenticated store.
     * 
     * @queryParam product_id integer Filter by product. Example: 1
     * @queryParam warehouse_id integer Filter by warehouse. Example: 1
     * @queryParam low_stock boolean Filter low stock items. Example: 1
     * @queryParam out_of_stock boolean Filter out of stock items. Example: 1
     * @queryParam sort_by string Sort field. Example: available_quantity
     * @queryParam sort_order string Sort direction: asc, desc. Example: asc
     * @queryParam per_page integer Items per page (max 100). Example: 20
     * 
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "product_id": 5,
     *       "variant_id": null,
     *       "warehouse_id": 1,
     *       "quantity": 100,
     *       "reserved_quantity": 10,
     *       "available_quantity": 90,
     *       "low_stock_threshold": 10,
     *       "product": {
     *         "id": 5,
     *         "name": "Premium Laptop Pro"
     *       },
     *       "warehouse": {
     *         "id": 1,
     *         "name": "Main Warehouse"
     *       }
     *     }
     *   ],
     *   "meta": {
     *     "current_page": 1,
     *     "per_page": 20,
     *     "total": 90
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'product_id', 'warehouse_id', 'low_stock', 'out_of_stock',
            'sort_by', 'sort_order'
        ]);

        $perPage = min($request->input('per_page', 20), 100);
        
        $inventory = $this->inventoryService->getInventory($filters, $perPage);

        return response()->json($inventory);
    }

    /**
     * Get inventory details
     * 
     * Retrieve a single inventory record with stock movement history.
     * 
     * @urlParam id integer required Inventory ID. Example: 1
     * 
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": 1,
     *     "product_id": 5,
     *     "warehouse_id": 1,
     *     "quantity": 100,
     *     "reserved_quantity": 10,
     *     "available_quantity": 90,
     *     "low_stock_threshold": 10,
     *     "product": {
     *       "id": 5,
     *       "name": "Premium Laptop Pro"
     *     },
     *     "warehouse": {
     *       "id": 1,
     *       "name": "Main Warehouse"
     *     },
     *     "stock_movements": []
     *   }
     * }
     * 
     * @response 404 scenario="Not found" {
     *   "message": "Inventory not found"
     * }
     */
    public function show(int $id): JsonResponse
    {
        $inventory = $this->inventoryService->getInventoryRecord($id);

        return response()->json(['data' => $inventory]);
    }

    /**
     * Set inventory
     * 
     * Create or update inventory for a product in a warehouse.
     * If inventory exists for the product/variant/warehouse combination, it will be updated.
     * 
     * @bodyParam product_id integer required Product ID. Example: 5
     * @bodyParam variant_id integer Product variant ID (if applicable). Example: null
     * @bodyParam warehouse_id integer required Warehouse ID. Example: 1
     * @bodyParam quantity integer required Stock quantity. Example: 100
     * @bodyParam reserved_quantity integer Reserved stock quantity. Example: 0
     * @bodyParam low_stock_threshold integer Low stock alert threshold. Example: 10
     * 
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": 1,
     *     "product_id": 5,
     *     "warehouse_id": 1,
     *     "quantity": 100,
     *     "reserved_quantity": 0,
     *     "available_quantity": 100,
     *     "low_stock_threshold": 10
     *   }
     * }
     * 
     * @response 422 scenario="Validation failed" {
     *   "message": "The given data was invalid",
     *   "errors": {
     *     "product_id": ["Product is required for inventory"],
     *     "warehouse_id": ["Warehouse is required for inventory"]
     *   }
     * }
     */
    public function store(InventoryRequest $request): JsonResponse
    {
        $inventory = $this->inventoryService->setInventory($request->validated());

        return response()->json(['data' => $inventory]);
    }

    /**
     * Adjust stock
     * 
     * Adjust inventory quantity for a product. Creates a stock movement record.
     * Use this for purchases, returns, adjustments, damage, or lost stock.
     * 
     * @bodyParam product_id integer required Product ID. Example: 5
     * @bodyParam variant_id integer Product variant ID (if applicable). Example: null
     * @bodyParam warehouse_id integer required Warehouse ID. Example: 1
     * @bodyParam quantity integer required Quantity to adjust (positive number). Example: 50
     * @bodyParam type string required Movement type: purchase, sale, return, adjustment, damage, lost. Example: purchase
     * @bodyParam notes string Optional notes for the movement. Example: Received new shipment
     * 
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": 1,
     *     "product_id": 5,
     *     "warehouse_id": 1,
     *     "quantity": 150,
     *     "available_quantity": 150,
     *     "low_stock_threshold": 10
     *   }
     * }
     * 
     * @response 400 scenario="Insufficient stock" {
     *   "message": "Insufficient stock. Available: 10"
     * }
     */
    public function adjust(StockAdjustmentRequest $request): JsonResponse
    {
        try {
            $inventory = $this->inventoryService->adjustInventory(
                $request->product_id,
                $request->warehouse_id,
                $request->quantity,
                $request->type,
                $request->notes,
                $request->variant_id,
                auth()->id()
            );

            return response()->json(['data' => $inventory]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Reserve stock
     * 
     * Reserve stock for an order or checkout. Increases reserved_quantity but doesn't reduce total quantity.
     * 
     * @bodyParam product_id integer required Product ID. Example: 5
     * @bodyParam variant_id integer Product variant ID (if applicable). Example: null
     * @bodyParam warehouse_id integer required Warehouse ID. Example: 1
     * @bodyParam quantity integer required Quantity to reserve. Example: 5
     * 
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": 1,
     *     "product_id": 5,
     *     "warehouse_id": 1,
     *     "quantity": 100,
     *     "reserved_quantity": 15,
     *     "available_quantity": 85
     *   }
     * }
     * 
     * @response 400 scenario="Insufficient stock" {
     *   "message": "Insufficient stock. Available: 10, Requested: 15"
     * }
     */
    public function reserve(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $inventory = $this->inventoryService->reserveStock(
                $request->product_id,
                $request->warehouse_id,
                $request->quantity,
                $request->variant_id
            );

            return response()->json(['data' => $inventory]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Release reserved stock
     * 
     * Release previously reserved stock (e.g., when order is cancelled).
     * Decreases reserved_quantity without changing total quantity.
     * 
     * @bodyParam product_id integer required Product ID. Example: 5
     * @bodyParam variant_id integer Product variant ID (if applicable). Example: null
     * @bodyParam warehouse_id integer required Warehouse ID. Example: 1
     * @bodyParam quantity integer required Quantity to release. Example: 5
     * 
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": 1,
     *     "product_id": 5,
     *     "warehouse_id": 1,
     *     "quantity": 100,
     *     "reserved_quantity": 10,
     *     "available_quantity": 90
     *   }
     * }
     */
    public function release(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $inventory = $this->inventoryService->releaseReservedStock(
                $request->product_id,
                $request->warehouse_id,
                $request->quantity,
                $request->variant_id
            );

            return response()->json(['data' => $inventory]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Fulfill reserved stock
     * 
     * Fulfill an order by reducing both quantity and reserved_quantity.
     * Creates a "sale" stock movement record.
     * 
     * @bodyParam product_id integer required Product ID. Example: 5
     * @bodyParam variant_id integer Product variant ID (if applicable). Example: null
     * @bodyParam warehouse_id integer required Warehouse ID. Example: 1
     * @bodyParam quantity integer required Quantity to fulfill. Example: 5
     * @bodyParam notes string Optional notes. Example: Order #12345 fulfilled
     * 
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": 1,
     *     "product_id": 5,
     *     "warehouse_id": 1,
     *     "quantity": 95,
     *     "reserved_quantity": 10,
     *     "available_quantity": 85
     *   }
     * }
     */
    public function fulfill(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $inventory = $this->inventoryService->fulfillReservedStock(
                $request->product_id,
                $request->warehouse_id,
                $request->quantity,
                $request->variant_id,
                auth()->id(),
                $request->notes
            );

            return response()->json(['data' => $inventory]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Transfer stock
     * 
     * Transfer stock between warehouses. Reduces stock in source warehouse and increases in destination warehouse.
     * Creates stock movement records for both warehouses.
     * 
     * @bodyParam product_id integer required Product ID. Example: 5
     * @bodyParam variant_id integer Product variant ID (if applicable). Example: null
     * @bodyParam from_warehouse_id integer required Source warehouse ID. Example: 1
     * @bodyParam to_warehouse_id integer required Destination warehouse ID. Example: 2
     * @bodyParam quantity integer required Quantity to transfer. Example: 20
     * @bodyParam notes string Optional notes. Example: Transfer to secondary warehouse
     * 
     * @response 200 scenario="Success" {
     *   "from_inventory": {
     *     "id": 1,
     *     "warehouse_id": 1,
     *     "quantity": 80,
     *     "available_quantity": 80
     *   },
     *   "to_inventory": {
     *     "id": 2,
     *     "warehouse_id": 2,
     *     "quantity": 120,
     *     "available_quantity": 120
     *   }
     * }
     * 
     * @response 400 scenario="Insufficient stock" {
     *   "message": "Insufficient stock in source warehouse. Available: 10"
     * }
     */
    public function transfer(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
            'from_warehouse_id' => 'required|integer|exists:warehouses,id',
            'to_warehouse_id' => 'required|integer|exists:warehouses,id|different:from_warehouse_id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $result = $this->inventoryService->transferStock(
                $request->product_id,
                $request->from_warehouse_id,
                $request->to_warehouse_id,
                $request->quantity,
                $request->variant_id,
                auth()->id(),
                $request->notes
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Get product inventory
     * 
     * Get all inventory records for a specific product across all warehouses.
     * 
     * @urlParam productId integer required Product ID. Example: 5
     * 
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "warehouse_id": 1,
     *       "quantity": 100,
     *       "reserved_quantity": 10,
     *       "available_quantity": 90,
     *       "warehouse": {
     *         "id": 1,
     *         "name": "Main Warehouse"
     *       }
     *     },
     *     {
     *       "id": 2,
     *       "warehouse_id": 2,
     *       "quantity": 50,
     *       "reserved_quantity": 0,
     *       "available_quantity": 50,
     *       "warehouse": {
     *         "id": 2,
     *         "name": "Secondary Warehouse"
     *       }
     *     }
     *   ],
     *   "total_available": 140
     * }
     */
    public function byProduct(int $productId): JsonResponse
    {
        $inventory = $this->inventoryService->getProductInventory($productId);
        $totalStock = $this->inventoryService->getProductTotalStock($productId);

        return response()->json([
            'data' => $inventory,
            'total_available' => $totalStock
        ]);
    }

    /**
     * Get stock movements
     * 
     * Get stock movement history with optional filtering.
     * 
     * @queryParam inventory_id integer Filter by inventory record. Example: 1
     * @queryParam type string Filter by movement type. Example: purchase
     * @queryParam per_page integer Items per page (max 100). Example: 20
     * 
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "inventory_id": 1,
     *       "type": "purchase",
     *       "quantity": 100,
     *       "notes": "Initial stock",
     *       "user": {
     *         "id": 1,
     *         "name": "Admin User"
     *       },
     *       "created_at": "2026-04-06T10:00:00Z"
     *     }
     *   ]
     * }
     */
    public function movements(Request $request): JsonResponse
    {
        $query = StockMovement::query()
            ->with(['inventory.product', 'user']);

        if ($request->has('inventory_id')) {
            $query->where('inventory_id', $request->inventory_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $query->orderBy('created_at', 'desc');

        $perPage = min($request->input('per_page', 20), 100);
        $movements = $query->paginate($perPage);

        return response()->json($movements);
    }
}
