<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stores = Store::all();

        foreach ($stores as $store) {
            // Create 2 warehouses per store
            $warehouses = [];
            $warehouses[] = Warehouse::create([
                'store_id' => $store->id,
                'name' => 'Main Warehouse',
                'code' => 'WH-001',
                'address' => '123 Storage St',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10001',
                'country' => 'US',
                'is_active' => true,
            ]);

            $warehouses[] = Warehouse::create([
                'store_id' => $store->id,
                'name' => 'Secondary Warehouse',
                'code' => 'WH-002',
                'address' => '456 Distribution Ave',
                'city' => 'Los Angeles',
                'state' => 'CA',
                'postal_code' => '90001',
                'country' => 'US',
                'is_active' => true,
            ]);

            // Get all products for this store
            $products = Product::where('store_id', $store->id)->get();
            $user = User::where('store_id', $store->id)->first();

            $inventoryCount = 0;
            $movementCount = 0;

            // Create inventory for each product
            foreach ($products as $product) {
                // Create inventory in main warehouse
                $quantity = rand(50, 200);
                $inventory = Inventory::create([
                    'store_id' => $store->id,
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'warehouse_id' => $warehouses[0]->id,
                    'quantity' => $quantity,
                    'reserved_quantity' => rand(0, min($quantity, 20)),
                    'low_stock_threshold' => 10,
                ]);
                $inventoryCount++;

                // Create initial stock movement (purchase)
                StockMovement::create([
                    'store_id' => $store->id,
                    'inventory_id' => $inventory->id,
                    'type' => 'purchase',
                    'quantity' => $quantity,
                    'notes' => 'Initial stock',
                    'user_id' => $user?->id,
                ]);
                $movementCount++;

                // 30% chance to also have stock in secondary warehouse
                if (rand(1, 100) <= 30) {
                    $quantity2 = rand(20, 100);
                    $inventory2 = Inventory::create([
                        'store_id' => $store->id,
                        'product_id' => $product->id,
                        'variant_id' => null,
                        'warehouse_id' => $warehouses[1]->id,
                        'quantity' => $quantity2,
                        'reserved_quantity' => rand(0, min($quantity2, 10)),
                        'low_stock_threshold' => 5,
                    ]);
                    $inventoryCount++;

                    StockMovement::create([
                        'store_id' => $store->id,
                        'inventory_id' => $inventory2->id,
                        'type' => 'purchase',
                        'quantity' => $quantity2,
                        'notes' => 'Initial stock',
                        'user_id' => $user?->id,
                    ]);
                    $movementCount++;
                }
            }

            echo "[OK] Created 2 warehouses, {$inventoryCount} inventory records, and {$movementCount} stock movements for {$store->name}\n";
        }
    }
}
