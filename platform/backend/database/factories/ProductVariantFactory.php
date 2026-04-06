<?php

namespace Database\Factories;

use App\Models\ProductVariant;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $color = fake()->safeColorName();
        $size = fake()->randomElement(['Small', 'Medium', 'Large', 'XL']);
        
        return [
            'product_id' => Product::factory(),
            'store_id' => fn (array $attributes) => Product::find($attributes['product_id'])->store_id,
            'name' => "$color / $size",
            'sku' => strtoupper(fake()->bothify('??##??##-V')),
            'price' => null, // Use product price by default
            'compare_price' => null,
            'stock_quantity' => fake()->numberBetween(0, 50),
            'attributes' => [
                'color' => $color,
                'size' => $size,
            ],
            'image' => null,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the variant is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the variant has a different price.
     */
    public function withPrice(float $price): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => $price,
        ]);
    }
}
