<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(3, true);
        $price = fake()->randomFloat(2, 10, 1000);
        
        return [
            'store_id' => Store::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name) . '-' . fake()->unique()->numberBetween(1000, 9999),
            'sku' => strtoupper(fake()->bothify('??##??##')),
            'description' => fake()->paragraph(3),
            'short_description' => fake()->sentence(),
            'price' => $price,
            'compare_price' => fake()->optional(0.3)->randomFloat(2, $price + 10, $price + 200),
            'cost_price' => fake()->randomFloat(2, $price * 0.4, $price * 0.7),
            'track_inventory' => true,
            'stock_quantity' => fake()->numberBetween(0, 100),
            'low_stock_threshold' => 5,
            'weight' => fake()->randomFloat(2, 0.1, 50),
            'weight_unit' => 'kg',
            'dimensions' => [
                'length' => fake()->numberBetween(10, 100),
                'width' => fake()->numberBetween(10, 100),
                'height' => fake()->numberBetween(10, 100),
                'unit' => 'cm',
            ],
            'status' => 'active',
            'is_featured' => fake()->boolean(20), // 20% chance
            'meta_title' => fake()->sentence(),
            'meta_description' => fake()->sentence(),
        ];
    }

    /**
     * Indicate that the product is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Indicate that the product is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
        ]);
    }

    /**
     * Indicate that the product is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Indicate that the product is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => 0,
        ]);
    }

    /**
     * Indicate that the product has low stock.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => 3,
        ]);
    }
}
