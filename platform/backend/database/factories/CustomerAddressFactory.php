<?php

namespace Database\Factories;

use App\Models\CustomerAddress;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomerAddress>
 */
class CustomerAddressFactory extends Factory
{
    protected $model = CustomerAddress::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'store_id' => fn (array $attributes) => Customer::find($attributes['customer_id'])->store_id,
            'type' => fake()->randomElement(['shipping', 'billing', 'both']),
            'label' => fake()->randomElement(['Home', 'Office', 'Other']),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'company' => fake()->optional(0.3)->company(),
            'address_line1' => fake()->streetAddress(),
            'address_line2' => fake()->optional(0.3)->secondaryAddress(),
            'city' => fake()->city(),
            'state_province' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'country' => 'US',
            'phone' => '+1' . fake()->numerify('##########'),
            'is_default' => false,
        ];
    }

    /**
     * Indicate that this is the default address.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Indicate that this is a shipping address.
     */
    public function shipping(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'shipping',
        ]);
    }

    /**
     * Indicate that this is a billing address.
     */
    public function billing(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'billing',
        ]);
    }
}
