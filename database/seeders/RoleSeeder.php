<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure Roles Exists
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin'], ['level' => 1000]); 
        $adminRole = Role::firstOrCreate(['name' => 'Admin'], ['level' => 100]);
        $ownerRole = Role::firstOrCreate(['name' => 'Dueño'], ['level' => 50]);
        $managerRole = Role::firstOrCreate(['name' => 'Administrador'], ['level' => 30]);
        $operatorRole = Role::firstOrCreate(['name' => 'Operador'], ['level' => 10]);
        $cashierRole = Role::firstOrCreate(['name' => 'Cajero'], ['level' => 10]);
        $sellerRole = Role::firstOrCreate(['name' => 'Vendedor'], ['level' => 10]);
        $driverRole = Role::firstOrCreate(['name' => 'Driver'], ['level' => 10]);

        // Update levels just in case
        $superAdminRole->update(['level' => 1000]);
        $adminRole->update(['level' => 100]);
        $ownerRole->update(['level' => 50]);
        $managerRole->update(['level' => 30]);
        $operatorRole->update(['level' => 10]);
        $cashierRole->update(['level' => 10]);
        $sellerRole->update(['level' => 10]);

        // 2. Assign Permissions

        // Super Admin & Admin: Everything
        $allPermissions = Permission::all();
        $adminRole->syncPermissions($allPermissions);

        // Dueño: Almost everything, except maybe technical system settings
        $ownerPermissions = $allPermissions->reject(function ($permission) {
             return in_array($permission->name, [
                 'roles.index', 'roles.create', 'roles.edit', 'roles.delete', 'permissions.assign',
                 'settings.backups', 'settings.logs', 'settings.update'
             ]);
        });
        $ownerRole->syncPermissions($ownerPermissions);

        // Administrador: Manage Users, Customers, Inventory, etc.
        $managerPermissions = Permission::whereIn('name', [
            // Sales
            'sales.index', 'sales.create', 'sales.edit', 'sales.pdf',
            'cash_register.open', 'cash_register.close', 'cash_register.access',
            // Products & Categories
            'products.index', 'products.create', 'products.edit', 'products.import',
            'categories.index', 'categories.create', 'categories.edit',
            // People
            'customers.index', 'customers.create', 'customers.edit',
            'suppliers.index', 'suppliers.create', 'suppliers.edit',
            'users.index', 'users.create', 'users.edit', 
            // Operations
            'purchases.index', 'purchases.create', 'purchases.edit',
            'inventory.index', 'adjustments.create', 'transfers.create', 'warehouses.index',
            // Reports
            'reports.sales', 'reports.purchases', 'reports.stock', 'reports.financial', 'reports.commissions',
            // View All
            'sales.view_all', 'customers.view_all',
            'orders.view_all', 'orders.add_to_cart', 'orders.delete', 'orders.edit', 'orders.details', 'orders.pdf',
            'payments.view_all','payments.pay', 'payments.history', 'payments.print_receipt', 'payments.view_proof', 'payments.print_history',
            'payments.approve', 'payments.register_direct',
        ])->get();
        $managerRole->syncPermissions($managerPermissions);

        // Operational Roles (Cajero, Vendedor, Operador)
        $operationalPermissions = Permission::whereIn('name', [
            'sales.index', 'sales.create', 'sales.pdf',
            'cash_register.open', 'cash_register.close', 'cash_register.access',
            'customers.index', 'customers.create', 'customers.edit',
            'products.index', // Search products
            'products.create', // Sometimes they need to create products? Let's check user intent. Usually no.
            'inventory.index', // Check stock
        ])->get();

        $operatorRole->syncPermissions($operationalPermissions);
        $cashierRole->syncPermissions($operationalPermissions);
        
        // Seller specific (View Own)
        $sellerPermissions = $operationalPermissions->merge(Permission::whereIn('name', [
            'sales.view_own', 'customers.view_own',
            'orders.view_own', 'orders.add_to_cart', 'orders.delete', 'orders.edit', 'orders.details', 'orders.pdf',
            'payments.view_own', 'payments.pay', 'payments.history', 'payments.print_receipt', 'payments.view_proof', 'payments.print_history',
            // Payment Workflow
            'payments.upload', 
        ])->get());
        
        $sellerRole->syncPermissions($sellerPermissions);
    }
}
