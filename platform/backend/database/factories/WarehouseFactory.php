<?php

namespace Database\Factories;

use App\Models\Warehouse;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Warehouse>
 */
class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $warehouseNames = [
            'Main Warehouse',
            'Distribution Center',
            'Storage Facility',
            'Fulfillment Center',
            'Regional Depot',
        ];

        return [
            'store_id' => Store::factory(),
            'name' => fake()->randomElement($warehouseNames) . ' - ' . fake()->city(),
            'code' => strtoupper(fake()->unique()->bothify('WH-###')),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'postal_code' => fake()->postcode(),
            'country' => 'US',
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the warehouse is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
