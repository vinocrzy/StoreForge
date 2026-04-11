<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $seedDemoStore = filter_var(env('SEED_DEMO_STORE', true), FILTER_VALIDATE_BOOL);
        $seedDemoData = filter_var(env('SEED_DEMO_DATA', false), FILTER_VALIDATE_BOOL);
        $seedMockData = filter_var(env('SEED_MOCK_DATA', false), FILTER_VALIDATE_BOOL);

        $this->call([
            CoreSeeder::class,
        ]);

        if ($seedDemoStore) {
            $this->call([
                DemoStoreSeeder::class,
            ]);
        }

        if ($seedDemoData || $seedMockData) {
            $this->call([
                DemoCatalogSeeder::class,
            ]);
        }

        $seedClientCatalogs = filter_var(env('SEED_CLIENT_CATALOGS', true), FILTER_VALIDATE_BOOL);
        if ($seedClientCatalogs) {
            $this->call([
                StorefrontTemplateCatalogSeeder::class,
                HoneyBeeCatalogSeeder::class,
            ]);
        }
    }
}
