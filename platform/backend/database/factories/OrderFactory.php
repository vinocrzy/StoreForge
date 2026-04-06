<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled']);
        $paymentStatus = fake()->randomElement(['pending', 'paid', 'failed']);
        $fulfillmentStatus = fake()->randomElement(['unfulfilled', 'partial', 'fulfilled']);
        
        $subtotal = fake()->randomFloat(2, 20, 500);
        $discountAmount = fake()->boolean(30) ? fake()->randomFloat(2, 5, 50) : 0;
        $shippingAmount = fake()->randomFloat(2, 5, 25);
        $taxAmount = $subtotal * 0.1; // 10% tax
        $total = $subtotal - $discountAmount + $shippingAmount + $taxAmount;
        
        $placedAt = fake()->dateTimeBetween('-3 months', 'now');
        
        return [
            'store_id' => Store::factory(),
            'customer_id' => Customer::factory(),
            'order_number' => 'ORD-' . fake()->unique()->numerify('######'),
            'status' => $status,
            'payment_status' => $paymentStatus,
            'fulfillment_status' => $fulfillmentStatus,
            'currency' => 'USD',
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'shipping_amount' => $shippingAmount,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'coupon_code' => fake()->boolean(20) ? fake()->bothify('??##-??##') : null,
            'customer_note' => fake()->boolean(30) ? fake()->sentence() : null,
            'admin_note' => fake()->boolean(20) ? fake()->sentence() : null,
            'payment_method' => fake()->randomElement(['manual', 'bank_transfer', 'cash_on_delivery', 'card']),
            'paid_at' => $paymentStatus === 'paid' ? fake()->dateTimeBetween($placedAt, 'now') : null,
            'paid_by_user_id' => ($paymentStatus === 'paid' && fake()->boolean(80)) ? User::factory() : null,
            'payment_notes' => fake()->boolean(20) ? fake()->sentence() : null,
            'payment_proof_url' => fake()->boolean(10) ? fake()->imageUrl(640, 480, 'receipt') : null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'placed_at' => $placedAt,
            'confirmed_at' => in_array($status, ['confirmed', 'processing', 'shipped', 'delivered']) ? fake()->dateTimeBetween($placedAt, 'now') : null,
            'shipped_at' => in_array($status, ['shipped', 'delivered']) ? fake()->dateTimeBetween($placedAt, 'now') : null,
            'delivered_at' => $status === 'delivered' ? fake()->dateTimeBetween($placedAt, 'now') : null,
            'cancelled_at' => $status === 'cancelled' ? fake()->dateTimeBetween($placedAt, 'now') : null,
        ];
    }

    /**
     * Order with pending status
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'payment_status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'confirmed_at' => null,
            'shipped_at' => null,
            'delivered_at' => null,
            'cancelled_at' => null,
        ]);
    }

    /**
     * Order with delivered status
     */
    public function delivered(): static
    {
        $placedAt = fake()->dateTimeBetween('-3 months', '-1 week');
        
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'payment_status' => 'paid',
            'fulfillment_status' => 'fulfilled',
            'placed_at' => $placedAt,
            'confirmed_at' => fake()->dateTimeBetween($placedAt, '-5 days'),
            'shipped_at' => fake()->dateTimeBetween($placedAt, '-3 days'),
            'delivered_at' => fake()->dateTimeBetween($placedAt, 'now'),
            'cancelled_at' => null,
        ]);
    }

    /**
     * Order with paid payment
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'paid',
            'paid_at' => now(),
            'paid_by_user_id' => User::factory(),
        ]);
    }
}
