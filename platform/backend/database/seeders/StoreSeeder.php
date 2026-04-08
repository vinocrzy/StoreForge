<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $demoStoreSlug = env('DEMO_STORE_SLUG', 'demo-store');
        $demoStoreDomain = env('DEMO_STORE_DOMAIN', 'demo.localhost');

        Store::updateOrCreate(
            ['slug' => $demoStoreSlug],
            [
                'name' => env('DEMO_STORE_NAME', 'Demo Store'),
                'domain' => $demoStoreDomain,
                'status' => 'active',
                'email' => env('DEMO_STORE_EMAIL', 'demo@store.local'),
                'phone' => env('DEMO_STORE_PHONE', '+12025550101'),
                'address' => [
                    'street' => '123 Demo Street',
                    'city' => 'New York',
                    'state' => 'NY',
                    'country' => 'USA',
                    'zip' => '10001',
                ],
                'currency' => env('DEMO_STORE_CURRENCY', 'USD'),
                'timezone' => env('DEMO_STORE_TIMEZONE', 'America/New_York'),
                'language' => env('DEMO_STORE_LANGUAGE', 'en'),
                'settings' => [
                    'theme' => 'default',
                    'logo_text' => env('DEMO_STORE_NAME', 'Demo Store'),
                    'tagline' => 'Demo tenant for QA and onboarding',
                ],
            ]
        );

        $this->command->info('✓ 1 demo store ensured (' . $demoStoreSlug . ')');
    }
}
