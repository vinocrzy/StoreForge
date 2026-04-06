<?php

namespace Database\Factories;

use App\Models\StockMovement;
use App\Models\Store;
use App\Models\Inventory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockMovement>
 */
class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['purchase', 'sale', 'return', 'adjustment', 'damage', 'lost'];
        $type = fake()->randomElement($types);
        
        // Positive quantities for purchase, return, adjustment
        // Negative for sale, damage, lost
        $quantity = in_array($type, ['purchase', 'return', 'adjustment'])
            ? fake()->numberBetween(1, 50)
            : fake()->numberBetween(-50, -1);

        return [
            'store_id' => Store::factory(),
            'inventory_id' => Inventory::factory(),
            'type' => $type,
            'quantity' => $quantity,
            'reference_type' => null,
            'reference_id' => null,
            'notes' => fake()->optional(0.3)->sentence(),
            'user_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the movement is a purchase.
     */
    public function purchase(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'purchase',
            'quantity' => fake()->numberBetween(10, 100),
        ]);
    }

    /**
     * Indicate that the movement is a sale.
     */
    public function sale(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'sale',
            'quantity' => fake()->numberBetween(-20, -1),
            'reference_type' => 'Order',
        ]);
    }

    /**
     * Indicate that the movement is an adjustment.
     */
    public function adjustment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'adjustment',
            'quantity' => fake()->numberBetween(-10, 10),
            'notes' => 'Stock count adjustment',
        ]);
    }
}
