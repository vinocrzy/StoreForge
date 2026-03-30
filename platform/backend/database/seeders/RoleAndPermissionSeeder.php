<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Store Management
            'view stores',
            'create stores',
            'edit stores',
            'delete stores',
            
            // User Management
            'view users',
            'create users',
            'edit users',
            'delete users',
            'manage roles',
            
            // Product Management
            'view products',
            'create products',
            'edit products',
            'delete products',
            'manage inventory',
            
            // Order Management
            'view orders',
            'create orders',
            'edit orders',
            'delete orders',
            'process orders',
            'refund orders',
            
            // Customer Management
            'view customers',
            'create customers',
            'edit customers',
            'delete customers',
            
            // Reports & Analytics
            'view reports',
            'export reports',
            
            // Settings
            'manage settings',
            'manage payment methods',
            'manage shipping methods',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        
        // Super Admin - has all permissions
        $superAdmin = Role::create(['name' => 'super-admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Store Owner - full access to their store
        $owner = Role::create(['name' => 'owner']);
        $owner->givePermissionTo(Permission::all());

        // Store Admin - most permissions except store deletion
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo([
            'view stores', 'edit stores',
            'view users', 'create users', 'edit users',
            'view products', 'create products', 'edit products', 'delete products', 'manage inventory',
            'view orders', 'create orders', 'edit orders', 'process orders', 'refund orders',
            'view customers', 'create customers', 'edit customers',
            'view reports', 'export reports',
            'manage settings', 'manage payment methods', 'manage shipping methods',
        ]);

        // Store Manager - operational permissions
        $manager = Role::create(['name' => 'manager']);
        $manager->givePermissionTo([
            'view stores',
            'view users',
            'view products', 'edit products', 'manage inventory',
            'view orders', 'edit orders', 'process orders',
            'view customers', 'edit customers',
            'view reports',
        ]);

        // Staff - basic permissions
        $staff = Role::create(['name' => 'staff']);
        $staff->givePermissionTo([
            'view products',
            'view orders', 'edit orders',
            'view customers',
        ]);

        $this->command->info('✓ Roles and permissions created');
    }
}
