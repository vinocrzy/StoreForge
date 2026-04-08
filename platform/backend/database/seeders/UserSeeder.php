<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $seedSuperAdmin = filter_var(env('SEED_SUPER_ADMIN', true), FILTER_VALIDATE_BOOL);
        $seedDemoStoreUsers = filter_var(env('SEED_DEMO_STORE_USERS', true), FILTER_VALIDATE_BOOL);

        // Get all stores
        $stores = Store::all();

        if ($seedSuperAdmin) {
            // Keep fixed super admin credentials for bootstrap operations.
            $superAdmin = User::firstOrCreate(
                ['email' => 'admin@ecommerce-platform.com'],
                [
                    'name' => 'Super Admin',
                    'password' => Hash::make('password'),
                    'phone' => '+12025550000',
                    'status' => 'active',
                ]
            );

            if (!$superAdmin->phone) {
                $superAdmin->phone = '+12025550000';
                $superAdmin->save();
            }

            $superAdmin->assignRole('super-admin');

            // Attach super admin to all stores as owner.
            foreach ($stores as $store) {
                $superAdmin->stores()->syncWithoutDetaching([
                    $store->id => ['role' => 'owner'],
                ]);
            }

            $this->command->info('✓ Super admin ensured: admin@ecommerce-platform.com / password');
        }

        if (!$seedDemoStoreUsers) {
            $this->command->info('✓ Demo store users skipped (SEED_DEMO_STORE_USERS=false)');
            return;
        }

        // Create users for each store
        foreach ($stores as $store) {
            // Store Owner
            $owner = User::firstOrCreate([
                'email' => 'owner@' . $store->slug . '.com',
            ], [
                'name' => $store->name . ' Owner',
                'password' => Hash::make('password'),
                'phone' => '+12025551001',
                'status' => 'active',
            ]);
            $owner->assignRole('owner');
            $owner->stores()->syncWithoutDetaching([$store->id => ['role' => 'owner']]);

            // Store Admin
            $admin = User::firstOrCreate([
                'email' => 'admin@' . $store->slug . '.com',
            ], [
                'name' => $store->name . ' Admin',
                'password' => Hash::make('password'),
                'phone' => '+12025551002',
                'status' => 'active',
            ]);
            $admin->assignRole('admin');
            $admin->stores()->syncWithoutDetaching([$store->id => ['role' => 'admin']]);

            // Store Manager
            $manager = User::firstOrCreate([
                'email' => 'manager@' . $store->slug . '.com',
            ], [
                'name' => $store->name . ' Manager',
                'password' => Hash::make('password'),
                'phone' => '+12025551003',
                'status' => 'active',
            ]);
            $manager->assignRole('manager');
            $manager->stores()->syncWithoutDetaching([$store->id => ['role' => 'manager']]);

            // Store Staff
            $staff = User::firstOrCreate([
                'email' => 'staff@' . $store->slug . '.com',
            ], [
                'name' => $store->name . ' Staff',
                'password' => Hash::make('password'),
                'phone' => '+12025551004',
                'status' => 'active',
            ]);
            $staff->assignRole('staff');
            $staff->stores()->syncWithoutDetaching([$store->id => ['role' => 'staff']]);

            $this->command->info('✓ Users created for ' . $store->name);
        }

        $this->command->info('');
        $this->command->info('=== TEST CREDENTIALS ===');
        if ($seedSuperAdmin) {
            $this->command->info('Super Admin: admin@ecommerce-platform.com / password');
        }
        $this->command->info('');
        if ($seedDemoStoreUsers) {
            foreach ($stores as $store) {
                $this->command->info($store->name . ':');
                $this->command->info('  Owner:   owner@' . $store->slug . '.com / password');
                $this->command->info('  Admin:   admin@' . $store->slug . '.com / password');
                $this->command->info('  Manager: manager@' . $store->slug . '.com / password');
                $this->command->info('  Staff:   staff@' . $store->slug . '.com / password');
            }
        }
    }
}
