<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stores = Store::all();
        
        $emailCounter = 1;
        $phoneCounter = 1000;

        foreach ($stores as $store) {
            // Create 15 customers per store
            for ($i = 1; $i <= 15; $i++) {
                $firstName = fake()->firstName();
                $lastName = fake()->lastName();
                
                $customer = Customer::create([
                    'store_id' => $store->id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => 'customer' . $emailCounter . '@example.com',
                    'phone' => '+1555' . str_pad($phoneCounter, 7, '0', STR_PAD_LEFT),
                    'password' => Hash::make('password'),
                    'status' => fake()->randomElement(['active', 'active', 'active', 'inactive']), // 75% active
                    'date_of_birth' => fake()->optional(0.7)->dateTimeBetween('-60 years', '-18 years'),
                    'gender' => fake()->optional(0.8)->randomElement(['male', 'female', 'other']),
                    'email_verified_at' => fake()->optional(0.8)->dateTimeBetween('-1 year', 'now'),
                    'phone_verified_at' => now(),
                    'last_login_at' => fake()->optional(0.6)->dateTimeBetween('-30 days', 'now'),
                ]);
                
                $emailCounter++;
                $phoneCounter++;

                // Create 1-3 addresses per customer
                $addressCount = fake()->numberBetween(1, 3);
                for ($j = 0; $j < $addressCount; $j++) {
                    CustomerAddress::create([
                        'customer_id' => $customer->id,
                        'store_id' => $store->id,
                        'type' => $j === 0 ? 'both' : fake()->randomElement(['shipping', 'billing']),
                        'label' => fake()->randomElement(['Home', 'Office', 'Other']),
                        'first_name' => $customer->first_name,
                        'last_name' => $customer->last_name,
                        'company' => fake()->optional(0.3)->company(),
                        'address_line1' => fake()->streetAddress(),
                        'address_line2' => fake()->optional(0.3)->secondaryAddress(),
                        'city' => fake()->city(),
                        'state_province' => fake()->state(),
                        'postal_code' => fake()->postcode(),
                        'country' => 'US',
                        'phone' => $customer->phone,
                        'is_default' => $j === 0, // First address is default
                    ]);
                }
            }

            $customerCount = Customer::where('store_id', $store->id)->count();
            $addressCount = CustomerAddress::where('store_id', $store->id)->count();
            echo "[OK] Created {$customerCount} customers with {$addressCount} addresses for {$store->name}\n";
        }
    }
}
