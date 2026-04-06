<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+1' . fake()->numerify('##########'), // E.164 format
            'password' => Hash::make('password'), // Default password for testing
            'status' => 'active',
            'date_of_birth' => fake()->optional(0.7)->dateTimeBetween('-60 years', '-18 years'),
            'gender' => fake()->optional(0.8)->randomElement(['male', 'female', 'other']),
            'notes' => fake()->optional(0.2)->sentence(),
            'metadata' => null,
            'email_verified_at' => fake()->optional(0.8)->dateTimeBetween('-1 year', 'now'),
            'phone_verified_at' => now(), // Most customers have verified phone
            'last_login_at' => fake()->optional(0.6)->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Indicate that the customer is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the customer is banned.
     */
    public function banned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'banned',
        ]);
    }

    /**
     * Indicate that the customer has not verified email.
     */
    public function unverifiedEmail(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the customer has not verified phone.
     */
    public function unverifiedPhone(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone_verified_at' => null,
        ]);
    }
}
