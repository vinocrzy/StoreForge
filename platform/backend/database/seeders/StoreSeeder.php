<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $stores = [
            [
                'name' => 'Demo Fashion Store',
                'slug' => 'demo-fashion',
                'domain' => 'fashion.demo.localhost',
                'status' => 'active',
                'email' => 'contact@fashionstore.com',
                'phone' => '+1-555-0101',
                'address' => [
                    'street' => '123 Fashion Ave',
                    'city' => 'New York',
                    'state' => 'NY',
                    'country' => 'USA',
                    'zip' => '10001',
                ],
                'currency' => 'USD',
                'timezone' => 'America/New_York',
                'language' => 'en',
                'settings' => [
                    'theme' => 'modern',
                    'color_primary' => '#000000',
                    'color_secondary' => '#ffffff',
                    'logo_text' => 'Fashion Store',
                    'tagline' => 'Your Style, Our Passion',
                    'features' => [
                        'wishlist' => true,
                        'reviews' => true,
                        'compare' => true,
                    ],
                ],
            ],
            [
                'name' => 'Demo Electronics Store',
                'slug' => 'demo-electronics',
                'domain' => 'electronics.demo.localhost',
                'status' => 'active',
                'email' => 'info@electrostore.com',
                'phone' => '+1-555-0102',
                'address' => [
                    'street' => '456 Tech Blvd',
                    'city' => 'San Francisco',
                    'state' => 'CA',
                    'country' => 'USA',
                    'zip' => '94102',
                ],
                'currency' => 'USD',
                'timezone' => 'America/Los_Angeles',
                'language' => 'en',
                'settings' => [
                    'theme' => 'tech',
                    'color_primary' => '#0066cc',
                    'color_secondary' => '#ff6600',
                    'logo_text' => 'ElectroStore',
                    'tagline' => 'Latest Tech, Best Prices',
                    'features' => [
                        'wishlist' => true,
                        'reviews' => true,
                        'compare' => true,
                        'warranty_info' => true,
                    ],
                ],
            ],
            [
                'name' => 'Demo Home Decor Store',
                'slug' => 'demo-homedecor',
                'domain' => 'homedecor.demo.localhost',
                'status' => 'active',
                'email' => 'hello@homedecor.com',
                'phone' => '+1-555-0103',
                'address' => [
                    'street' => '789 Design St',
                    'city' => 'Austin',
                    'state' => 'TX',
                    'country' => 'USA',
                    'zip' => '73301',
                ],
                'currency' => 'USD',
                'timezone' => 'America/Chicago',
                'language' => 'en',
                'settings' => [
                    'theme' => 'minimal',
                    'color_primary' => '#8B7355',
                    'color_secondary' => '#F5F5DC',
                    'logo_text' => 'Home Decor',
                    'tagline' => 'Make Your House a Home',
                    'features' => [
                        'wishlist' => true,
                        'reviews' => true,
                        'room_visualizer' => true,
                    ],
                ],
            ],
        ];

        foreach ($stores as $storeData) {
            Store::create($storeData);
        }

        $this->command->info('✓ ' . count($stores) . ' demo stores created');
    }
}
