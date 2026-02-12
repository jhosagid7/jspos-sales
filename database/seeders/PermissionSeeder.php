<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Defined Permissions grouped by Module
        $permissions = [
            // Sales
            'sales.index',
            'sales.create',
            'sales.edit',
            'sales.delete',
            'sales.pdf',
            'sales.pdf',
            'sales.view_all', // New: View all sales
            'sales.view_own', // New: View only own sales
            'orders.view_all',
            'orders.view_own',
            'orders.add_to_cart',
            'orders.delete',
            'orders.edit',
            'orders.details',
            'orders.pdf',
            'payments.view_all',
            'payments.view_own',
            'payments.pay',
            'payments.history',
            'payments.print_receipt',
            'payments.view_proof',
            'payments.print_history',
            'payments.print_pdf',       // New: Print PDF History
            'payments.upload',          // New: Upload pending payment
            'payments.approve',         // New: Approve pending payment
            'payments.register_direct', // New: Register approved payment directly
            'payments.delete',          // New: Delete payment
            'sales.approve_deletion', // Special permission for approving deletions
            'sales.manage_adjustments', // New: Manage Price Adjustments (Commissions/Freight)
            
            // Cash Register
            'cash_register.open',
            'cash_register.close',
            'cash_register.access',
            
            // Products
            'products.index',
            'products.create',
            'products.edit',
            'products.delete',
            'products.import',
            'products.labels', // Generate labels/barcodes
            
            // Categories
            'categories.index',
            'categories.create',
            'categories.edit',
            'categories.delete',
            
            // Customers
            'customers.index',
            'customers.create',
            'customers.edit',
            'customers.edit',
            'customers.delete',
            'customers.view_all', // New: View all customers
            'customers.view_own', // New: View only own customers
            
            // Suppliers
            'suppliers.index',
            'suppliers.create',
            'suppliers.edit',
            'suppliers.delete',
            
            // Purchases
            'purchases.index',
            'purchases.create',
            'purchases.edit',
            'purchases.delete',
            
            // Inventory
            'inventory.index',       // View general inventory
            'adjustments.create',    // Cargos/Descargos (General)
            'adjustments.approve',   // Approve adjustments
            'transfers.create',      // Create transfers
            'warehouses.index',
            'warehouses.create',
            'warehouses.edit',
            'warehouses.delete',
            
            // Users
            'users.index',
            'users.create',
            'users.edit',
            'users.delete',
            
            // Roles & Permissions
            'roles.index',
            'roles.create',
            'roles.edit',
            'roles.delete',
            'permissions.assign',
            
            // Reports
            'reports.sales',
            'reports.purchases',
            'reports.stock',
            'reports.financial', // Accounts receivable/payable
            'reports.commissions',
            
            // Settings
            'settings.index',
            'settings.backups',
            'settings.logs',
            'settings.update',
            
            // Production
            'production.index',
            'production.create',
            'production.delete',

            // Distribution
            'distribution.map', // Driver map access
        ];

        // Ensure permissions exist
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }
}
