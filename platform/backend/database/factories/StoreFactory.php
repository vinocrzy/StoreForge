<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class StoreFactory extends Factory
{
    protected $model = Store::class;

    public function definition(): array
    {
        $name = fake()->company();
        
        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->numberBetween(1000, 9999),
            'domain' => fake()->unique()->domainName(),
            'status' => 'active',
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => [
                'street' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state' => fake()->state(),
                'country' => fake()->country(),
                'zip' => fake()->postcode(),
            ],
            'currency' => 'USD',
            'timezone' => 'UTC',
            'language' => 'en',
            'settings' => [],
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }
}
