<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\Store;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inventory>
 */
class InventoryFactory extends Factory
{
    protected $model = Inventory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(0, 200);
        $reserved = fake()->numberBetween(0, min($quantity, 20));

        return [
            'store_id' => Store::factory(),
            'product_id' => Product::factory(),
            'variant_id' => null, // Will be set by seeder if needed
            'warehouse_id' => Warehouse::factory(),
            'quantity' => $quantity,
            'reserved_quantity' => $reserved,
            'low_stock_threshold' => fake()->numberBetween(5, 20),
        ];
    }

    /**
     * Indicate that the inventory has low stock.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => fake()->numberBetween(1, 10),
            'reserved_quantity' => 0,
            'low_stock_threshold' => 10,
        ]);
    }

    /**
     * Indicate that the inventory is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 0,
            'reserved_quantity' => 0,
        ]);
    }

    /**
     * Indicate that the inventory has high stock.
     */
    public function inStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => fake()->numberBetween(100, 500),
            'reserved_quantity' => fake()->numberBetween(0, 10),
        ]);
    }
}
