<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 5);
        $price = fake()->randomFloat(2, 10, 200);
        $discountAmount = fake()->boolean(20) ? fake()->randomFloat(2, 1, 20) : 0;
        $taxAmount = ($price * $quantity) * 0.1; // 10% tax
        $total = ($price * $quantity) - $discountAmount + $taxAmount;
        
        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'variant_id' => null, // Will be set in seeder if needed
            'quantity' => $quantity,
            'price' => $price,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'product_snapshot' => [
                'name' => fake()->words(3, true),
                'sku' => fake()->bothify('SKU-####-????'),
                'description' => fake()->sentence(),
                'image_url' => fake()->imageUrl(400, 400, 'products'),
            ],
        ];
    }

    /**
     * Order item with specific product
     */
    public function forProduct(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
            'price' => $product->price,
            'product_snapshot' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'description' => $product->description,
                'image_url' => $product->primary_image_url,
            ],
        ]);
    }
}
