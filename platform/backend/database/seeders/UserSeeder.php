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
        // Get all stores
        $stores = Store::all();

        // Create a super admin (has access to all stores)
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@ecommerce-platform.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $superAdmin->assignRole('super-admin');
        
        // Attach super admin to all stores as owner
        foreach ($stores as $store) {
            $superAdmin->stores()->attach($store->id, ['role' => 'owner']);
        }

        $this->command->info('✓ Super admin created: admin@ecommerce-platform.com');

        // Create users for each store
        foreach ($stores as $store) {
            // Store Owner
            $owner = User::create([
                'name' => $store->name . ' Owner',
                'email' => 'owner@' . $store->slug . '.com',
                'password' => Hash::make('password'),
                'status' => 'active',
            ]);
            $owner->assignRole('owner');
            $owner->stores()->attach($store->id, ['role' => 'owner']);

            // Store Admin
            $admin = User::create([
                'name' => $store->name . ' Admin',
                'email' => 'admin@' . $store->slug . '.com',
                'password' => Hash::make('password'),
                'status' => 'active',
            ]);
            $admin->assignRole('admin');
            $admin->stores()->attach($store->id, ['role' => 'admin']);

            // Store Manager
            $manager = User::create([
                'name' => $store->name . ' Manager',
                'email' => 'manager@' . $store->slug . '.com',
                'password' => Hash::make('password'),
                'status' => 'active',
            ]);
            $manager->assignRole('manager');
            $manager->stores()->attach($store->id, ['role' => 'manager']);

            // Store Staff
            $staff = User::create([
                'name' => $store->name . ' Staff',
                'email' => 'staff@' . $store->slug . '.com',
                'password' => Hash::make('password'),
                'status' => 'active',
            ]);
            $staff->assignRole('staff');
            $staff->stores()->attach($store->id, ['role' => 'staff']);

            $this->command->info('✓ Users created for ' . $store->name);
        }

        $this->command->info('');
        $this->command->info('=== TEST CREDENTIALS ===');
        $this->command->info('Super Admin: admin@ecommerce-platform.com / password');
        $this->command->info('');
        foreach ($stores as $store) {
            $this->command->info($store->name . ':');
            $this->command->info('  Owner:   owner@' . $store->slug . '.com / password');
            $this->command->info('  Admin:   admin@' . $store->slug . '.com / password');
            $this->command->info('  Manager: manager@' . $store->slug . '.com / password');
            $this->command->info('  Staff:   staff@' . $store->slug . '.com / password');
        }
    }
}
