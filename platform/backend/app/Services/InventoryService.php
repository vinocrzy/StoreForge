<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Warehouse;
use App\Models\StockMovement;
use App\Models\StockAlert;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Get paginated inventory records with optional filtering
     */
    public function getInventory(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Inventory::query()
            ->with(['product', 'variant', 'warehouse']);

        // Filter by product
        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        // Filter by warehouse
        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        // Filter by low stock
        if (isset($filters['low_stock']) && $filters['low_stock']) {
            $query->whereColumn('quantity', '<=', 'low_stock_threshold');
        }

        // Filter by out of stock
        if (isset($filters['out_of_stock']) && $filters['out_of_stock']) {
            $query->where('quantity', 0);
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Get all inventory records for CSV export (unpaginated)
     *
     * @param array $filters
     * @return Collection
     */
    public function getInventoryForExport(array $filters = []): Collection
    {
        $query = Inventory::query()->with(['product', 'warehouse']);

        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (isset($filters['low_stock']) && $filters['low_stock']) {
            $query->whereColumn('quantity', '<=', 'low_stock_threshold');
        }

        if (isset($filters['out_of_stock']) && $filters['out_of_stock']) {
            $query->where('quantity', 0);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->get();
    }

    /**
     * Get single inventory record
     */
    public function getInventoryRecord(int $id): Inventory
    {
        return Inventory::with(['product', 'variant', 'warehouse', 'stockMovements'])->findOrFail($id);
    }

    /**
     * Get inventory for a specific product across all warehouses
     */
    public function getProductInventory(int $productId): Collection
    {
        return Inventory::with(['warehouse'])
            ->where('product_id', $productId)
            ->get();
    }

    /**
     * Get total available quantity for a product across all warehouses
     */
    public function getProductTotalStock(int $productId, ?int $variantId = null): int
    {
        $query = Inventory::where('product_id', $productId);

        if ($variantId) {
            $query->where('variant_id', $variantId);
        }

        return $query->sum(DB::raw('quantity - reserved_quantity'));
    }

    /**
     * Create or update inventory record
     */
    public function setInventory(array $data): Inventory
    {
        $inventory = Inventory::updateOrCreate(
            [
                'product_id' => $data['product_id'],
                'variant_id' => $data['variant_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'],
            ],
            [
                'quantity' => $data['quantity'],
                'reserved_quantity' => $data['reserved_quantity'] ?? 0,
                'low_stock_threshold' => $data['low_stock_threshold'] ?? 10,
                'store_id' => tenant()->id,
            ]
        );

        $this->syncStockAlertForInventory($inventory);

        return $inventory->fresh();
    }

    /**
     * Adjust inventory quantity (add or subtract)
     */
    public function adjustInventory(
        int $productId,
        int $warehouseId,
        int $quantity,
        string $type,
        string $notes = null,
        ?int $variantId = null,
        ?int $userId = null
    ): Inventory {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $type, $notes, $variantId, $userId) {
            // Get or create inventory record
            $inventory = Inventory::firstOrCreate(
                [
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'warehouse_id' => $warehouseId,
                    'store_id' => tenant()->id,
                ],
                [
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                    'low_stock_threshold' => 10,
                ]
            );

            // Update quantity based on type
            if (in_array($type, ['purchase', 'return', 'adjustment_in'])) {
                $inventory->quantity += $quantity;
            } elseif (in_array($type, ['sale', 'damage', 'adjustment_out'])) {
                $inventory->quantity -= $quantity;
                
                if ($inventory->quantity < 0) {
                    throw new \Exception('Insufficient stock. Available: ' . $inventory->quantity + $quantity);
                }
            }

            $inventory->save();

            $this->syncStockAlertForInventory($inventory);

            // Record stock movement
            StockMovement::create([
                'store_id' => tenant()->id,
                'inventory_id' => $inventory->id,
                'type' => $type,
                'quantity' => $quantity,
                'notes' => $notes,
                'user_id' => $userId,
            ]);

            return $inventory->fresh();
        });
    }

    /**
     * Reserve stock for an order
     */
    public function reserveStock(int $productId, int $warehouseId, int $quantity, ?int $variantId = null): Inventory
    {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $variantId) {
            $inventory = Inventory::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->where('variant_id', $variantId)
                ->lockForUpdate()
                ->firstOrFail();

            $available = $inventory->quantity - $inventory->reserved_quantity;

            if ($available < $quantity) {
                throw new \Exception("Insufficient stock. Available: {$available}, Requested: {$quantity}");
            }

            $inventory->reserved_quantity += $quantity;
            $inventory->save();

            $this->syncStockAlertForInventory($inventory);

            return $inventory;
        });
    }

    /**
     * Release reserved stock (cancel order or return)
     */
    public function releaseReservedStock(int $productId, int $warehouseId, int $quantity, ?int $variantId = null): Inventory
    {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $variantId) {
            $inventory = Inventory::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->where('variant_id', $variantId)
                ->lockForUpdate()
                ->firstOrFail();

            $inventory->reserved_quantity -= $quantity;

            if ($inventory->reserved_quantity < 0) {
                $inventory->reserved_quantity = 0;
            }

            $inventory->save();

            $this->syncStockAlertForInventory($inventory);

            return $inventory;
        });
    }

    /**
     * Fulfill reserved stock (complete order)
     */
    public function fulfillReservedStock(
        int $productId,
        int $warehouseId,
        int $quantity,
        ?int $variantId = null,
        ?int $userId = null,
        string $notes = null
    ): Inventory {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $variantId, $userId, $notes) {
            $inventory = Inventory::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->where('variant_id', $variantId)
                ->lockForUpdate()
                ->firstOrFail();

            // Reduce both quantity and reserved quantity
            $inventory->quantity -= $quantity;
            $inventory->reserved_quantity -= $quantity;

            if ($inventory->quantity < 0 || $inventory->reserved_quantity < 0) {
                throw new \Exception('Invalid stock fulfillment. Insufficient quantity or reserved stock.');
            }

            $inventory->save();

            $this->syncStockAlertForInventory($inventory);

            // Record stock movement
            StockMovement::create([
                'store_id' => tenant()->id,
                'inventory_id' => $inventory->id,
                'type' => 'sale',
                'quantity' => $quantity,
                'notes' => $notes ?? 'Order fulfilled',
                'user_id' => $userId,
            ]);

            return $inventory;
        });
    }

    /**
     * Transfer stock between warehouses
     */
    public function transferStock(
        int $productId,
        int $fromWarehouseId,
        int $toWarehouseId,
        int $quantity,
        ?int $variantId = null,
        ?int $userId = null,
        string $notes = null
    ): array {
        return DB::transaction(function () use ($productId, $fromWarehouseId, $toWarehouseId, $quantity, $variantId, $userId, $notes) {
            // Reduce from source warehouse
            $fromInventory = Inventory::where('product_id', $productId)
                ->where('warehouse_id', $fromWarehouseId)
                ->where('variant_id', $variantId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($fromInventory->available_quantity < $quantity) {
                throw new \Exception("Insufficient stock in source warehouse. Available: {$fromInventory->available_quantity}");
            }

            $fromInventory->quantity -= $quantity;
            $fromInventory->save();
            $this->syncStockAlertForInventory($fromInventory);

            // Add to destination warehouse
            $toInventory = Inventory::firstOrCreate(
                [
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'warehouse_id' => $toWarehouseId,
                    'store_id' => tenant()->id,
                ],
                [
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                    'low_stock_threshold' => 10,
                ]
            );

            $toInventory->quantity += $quantity;
            $toInventory->save();
            $this->syncStockAlertForInventory($toInventory);

            // Record movements
            StockMovement::create([
                'store_id' => tenant()->id,
                'inventory_id' => $fromInventory->id,
                'type' => 'transfer_out',
                'quantity' => $quantity,
                'notes' => $notes ?? "Transfer to warehouse #{$toWarehouseId}",
                'user_id' => $userId,
            ]);

            StockMovement::create([
                'store_id' => tenant()->id,
                'inventory_id' => $toInventory->id,
                'type' => 'transfer_in',
                'quantity' => $quantity,
                'notes' => $notes ?? "Transfer from warehouse #{$fromWarehouseId}",
                'user_id' => $userId,
            ]);

            return [
                'from' => $fromInventory->fresh(),
                'to' => $toInventory->fresh(),
            ];
        });
    }

    /**
     * Get low stock products
     */
    public function getLowStockProducts(): Collection
    {
        return Inventory::with(['product', 'warehouse'])
            ->whereColumn('quantity', '<=', 'low_stock_threshold')
            ->where('quantity', '>', 0)
            ->get();
    }

    /**
     * Get out of stock products
     */
    public function getOutOfStockProducts(): Collection
    {
        return Inventory::with(['product', 'warehouse'])
            ->where('quantity', 0)
            ->get();
    }

    /**
     * Get stock movements with pagination and filtering
     */
    public function getStockMovements(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = StockMovement::query()
            ->with(['inventory.product', 'inventory.warehouse', 'user']);

        // Filter by product
        if (!empty($filters['product_id'])) {
            $query->whereHas('inventory', function ($q) use ($filters) {
                $q->where('product_id', $filters['product_id']);
            });
        }

        // Filter by warehouse
        if (!empty($filters['warehouse_id'])) {
            $query->whereHas('inventory', function ($q) use ($filters) {
                $q->where('warehouse_id', $filters['warehouse_id']);
            });
        }

        // Filter by type
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Filter by date range
        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Get inventory statistics
     */
    public function getStatistics(): array
    {
        $totalProducts = Inventory::distinct('product_id')->count('product_id');
        $totalQuantity = Inventory::sum('quantity');
        $totalReserved = Inventory::sum('reserved_quantity');
        $lowStock = Inventory::whereColumn('quantity', '<=', 'low_stock_threshold')
            ->where('quantity', '>', 0)
            ->count();
        $outOfStock = Inventory::where('quantity', 0)->count();

        $totalValue = Inventory::join('products', 'inventories.product_id', '=', 'products.id')
            ->sum(DB::raw('inventories.quantity * products.cost_per_item'));

        return [
            'total_products' => $totalProducts,
            'total_quantity' => $totalQuantity,
            'total_reserved' => $totalReserved,
            'total_available' => $totalQuantity - $totalReserved,
            'low_stock_count' => $lowStock,
            'out_of_stock_count' => $outOfStock,
            'total_value' => round($totalValue, 2),
            'warehouses_count' => Warehouse::active()->count(),
        ];
    }

    /**
     * Get warehouse details with inventory
     */
    public function getWarehouse(int $id): Warehouse
    {
        return Warehouse::with(['inventories.product'])->findOrFail($id);
    }

    /**
     * Get all active warehouses
     */
    public function getWarehouses(): Collection
    {
        return Warehouse::active()->get();
    }

    /**
     * Create warehouse
     */
    public function createWarehouse(array $data): Warehouse
    {
        $data['store_id'] = tenant()->id;
        return Warehouse::create($data);
    }

    /**
     * Update warehouse
     */
    public function updateWarehouse(int $id, array $data): Warehouse
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->update($data);
        return $warehouse->fresh();
    }

    /**
     * Delete warehouse
     */
    public function deleteWarehouse(int $id): bool
    {
        $warehouse = Warehouse::findOrFail($id);

        // Check if warehouse has inventory
        if ($warehouse->inventories()->exists()) {
            throw new \Exception('Cannot delete warehouse with existing inventory. Transfer stock first.');
        }

        return $warehouse->delete();
    }

    /**
     * Create/update/resolve stock alerts based on available quantity.
     */
    private function syncStockAlertForInventory(Inventory $inventory): void
    {
        $available = $inventory->available_quantity;

        if ($available <= 0) {
            StockAlert::updateOrCreate(
                [
                    'store_id' => tenant()->id,
                    'product_id' => $inventory->product_id,
                    'warehouse_id' => $inventory->warehouse_id,
                    'alert_type' => 'out_of_stock',
                    'status' => 'active',
                ],
                [
                    'threshold' => 0,
                    'current_quantity' => $available,
                    'resolved_at' => null,
                ]
            );

            StockAlert::where('store_id', tenant()->id)
                ->where('product_id', $inventory->product_id)
                ->where('warehouse_id', $inventory->warehouse_id)
                ->where('alert_type', 'low_stock')
                ->where('status', 'active')
                ->update(['status' => 'resolved', 'resolved_at' => now()]);

            return;
        }

        if ($available <= $inventory->low_stock_threshold) {
            StockAlert::updateOrCreate(
                [
                    'store_id' => tenant()->id,
                    'product_id' => $inventory->product_id,
                    'warehouse_id' => $inventory->warehouse_id,
                    'alert_type' => 'low_stock',
                    'status' => 'active',
                ],
                [
                    'threshold' => $inventory->low_stock_threshold,
                    'current_quantity' => $available,
                    'resolved_at' => null,
                ]
            );

            StockAlert::where('store_id', tenant()->id)
                ->where('product_id', $inventory->product_id)
                ->where('warehouse_id', $inventory->warehouse_id)
                ->where('alert_type', 'out_of_stock')
                ->where('status', 'active')
                ->update(['status' => 'resolved', 'resolved_at' => now()]);

            return;
        }

        StockAlert::where('store_id', tenant()->id)
            ->where('product_id', $inventory->product_id)
            ->where('warehouse_id', $inventory->warehouse_id)
            ->where('status', 'active')
            ->update(['status' => 'resolved', 'resolved_at' => now()]);
    }
}
