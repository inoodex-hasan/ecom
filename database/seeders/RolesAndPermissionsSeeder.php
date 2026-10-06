<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define modular permissions
        $permissions = [
            // Analytics Dashboard
            'dashboard.view',

            // Products
            'products.view',
            'products.create',
            'products.edit',
            'products.delete',

            // Orders
            'orders.view',
            'orders.edit',
            'orders.update_status',
            'orders.invoice',

            // Categories
            'categories.view',
            'categories.create',
            'categories.edit',
            'categories.delete',

            // Customers
            'customers.view',
            'customers.edit',

            // Settings
            'settings.view',
            'settings.edit',

            // Inventory
            'inventory.view',
            'inventory.adjust',

            // Banners & Sliders
            'banners.view',
            'banners.manage',

            // Promotions & Flash Sales
            'promotions.view',
            'promotions.manage',

            // Coupons & Promo Codes
            'coupons.view',
            'coupons.manage',

            // Customer Reviews & Ratings
            'reviews.view',
            'reviews.manage',

            // Staff & Roles
            'staff.view',
            'staff.manage',

            // Fraud Shield & Risk Control
            'fraud.view',
            'fraud.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // 1. Super Admin Role
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // 2. Store Manager Role
        $manager = Role::firstOrCreate(['name' => 'Store Manager', 'guard_name' => 'web']);
        $manager->syncPermissions([
            'dashboard.view',
            'products.view', 'products.create', 'products.edit', 'products.delete',
            'inventory.view', 'inventory.adjust',
            'banners.view', 'banners.manage',
            'promotions.view', 'promotions.manage',
            'coupons.view', 'coupons.manage',
            'reviews.view', 'reviews.manage',
            'orders.view', 'orders.edit', 'orders.update_status', 'orders.invoice',
            'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
            'customers.view', 'customers.edit',
            'settings.view',
            'fraud.view', 'fraud.manage',
        ]);

        // 3. Fulfillment Staff Role
        $fulfillment = Role::firstOrCreate(['name' => 'Fulfillment Staff', 'guard_name' => 'web']);
        $fulfillment->syncPermissions([
            'dashboard.view',
            'orders.view', 'orders.edit', 'orders.update_status', 'orders.invoice',
            'products.view',
            'inventory.view', 'inventory.adjust',
            'customers.view',
            'fraud.view',
        ]);

        // 4. Catalog Specialist Role
        $catalog = Role::firstOrCreate(['name' => 'Catalog Specialist', 'guard_name' => 'web']);
        $catalog->syncPermissions([
            'dashboard.view',
            'products.view', 'products.create', 'products.edit',
            'categories.view', 'categories.create', 'categories.edit',
        ]);

        // 5. Customer Support Role
        $support = Role::firstOrCreate(['name' => 'Customer Support', 'guard_name' => 'web']);
        $support->syncPermissions([
            'dashboard.view',
            'orders.view',
            'customers.view',
            'products.view',
        ]);

        // Assign Roles to existing seed users
        $adminEmails = ['admin@ecom.test', 'hello@inoodex.com'];
        foreach ($adminEmails as $email) {
            $adminUser = User::where('email', $email)->first();
            if ($adminUser) {
                $adminUser->syncRoles(['Super Admin']);
            }
        }

        $managerUser = User::where('email', 'manager@ecom.test')->first();
        if ($managerUser) {
            $managerUser->syncRoles(['Store Manager']);
        }
    }
}
