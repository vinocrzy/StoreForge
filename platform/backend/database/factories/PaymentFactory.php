<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(['pending', 'completed', 'failed']);
        $gateway = fake()->randomElement(['manual', 'stripe', 'paypal', 'razorpay']);
        $amount = fake()->randomFloat(2, 20, 500);
        
        return [
            'store_id' => Store::factory(),
            'order_id' => Order::factory(),
            'transaction_id' => $gateway !== 'manual' ? fake()->uuid() : null,
            'gateway' => $gateway,
            'payment_method' => fake()->randomElement(['bank_transfer', 'cash_on_delivery', 'card', 'upi', 'wallet']),
            'amount' => $amount,
            'currency' => 'USD',
            'status' => $status,
            'failure_reason' => $status === 'failed' ? fake()->sentence() : null,
            'metadata' => $gateway !== 'manual' ? [
                'payment_id' => fake()->bothify('pay_??########'),
                'reference' => fake()->bothify('REF-####-????'),
            ] : [
                'reference_number' => fake()->bothify('MAN-####-????'),
                'notes' => fake()->sentence(),
            ],
            'processed_at' => in_array($status, ['completed', 'failed']) ? fake()->dateTimeBetween('-1 month', 'now') : null,
        ];
    }

    /**
     * Manual payment
     */
    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway' => 'manual',
            'transaction_id' => null,
            'metadata' => [
                'reference_number' => fake()->bothify('MAN-####-????'),
                'notes' => fake()->sentence(),
            ],
        ]);
    }

    /**
     * Completed payment
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'processed_at' => now(),
            'failure_reason' => null,
        ]);
    }

    /**
     * Failed payment
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'processed_at' => now(),
            'failure_reason' => fake()->randomElement([
                'Insufficient funds',
                'Card declined',
                'Payment timeout',
                'Invalid card details',
            ]),
        ]);
    }
}
