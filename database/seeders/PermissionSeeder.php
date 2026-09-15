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
            // Sales / POS
            'sales.index',
            'sales.create',
            'sales.edit',
            'sales.delete',
            'sales.pdf',
            'sales.view_all',
            'sales.view_own',
            'sales.view_history',
            'sales.approve_deletion',
            'sales.manage_adjustments',
            'sales.show_exchange_rate',
            'sales.change_invoice_currency',
            'sales.mix_warehouses',
            'sales.switch_warehouse',
            'sales.select_driver',
            'sales.configure_price_list',
            'sales.generate_price_list',
            'pos.select_operator',
            'manage_debit_notes',

            // Orders / Preventa
            'orders.view_all',
            'orders.view_own',
            'orders.view_history',
            'orders.save',
            'orders.add_to_cart',
            'orders.delete',
            'orders.edit',
            'orders.details',
            'orders.pdf',

            // Payments / Cobranzas
            'payments.view_all',
            'payments.view_own',
            'payments.pay',
            'payments.history',
            'payments.print_receipt',
            'payments.view_proof',
            'payments.print_history',
            'payments.print_pdf',
            'payments.upload',
            'payments.approve',
            'payments.register_direct',
            'payments.delete',
            'payments.void_today',
            'payments.void_anytime',
            'payments.approve_custom_rate',
            'payments.force_discounts',
            'payments.methods',
            'payments.method_cash',
            'payments.method_bank',
            'payments.method_credit',
            'payments.method_nequi',
            
            // Cash Register / Arqueo
            'cash_register.open',
            'cash_register.close',
            'cash_register.access',
            'cash_register.bypass',
            'cash_register.view_all',
            'cash_register.view_own',
            
            // Products
            'products.index',
            'products.create',
            'products.edit',
            'products.delete',
            'products.import',
            'products.labels',
            'products.edit.inventory',
            'products.edit.categories',
            'products.edit.price_rules',
            
            // Categories
            'categories.index',
            'categories.create',
            'categories.edit',
            'categories.delete',
            
            // Customers
            'customers.index',
            'customers.create',
            'customers.edit',
            'customers.delete',
            'customers.import',
            'customers.view_all',
            'customers.view_own',
            'customers.edit_commercial_config',
            'customers.edit_credit_config',
            'customer_statement.index',
            'customer_statement.view_all',
            'customer_statement.view_own',
            
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
            
            // Inventory & Warehouses
            'inventory.index',
            'adjustments.create',
            'adjustments.approve',
            'adjustments.approve_cargo',
            'adjustments.approve_descargo',
            'adjustments.delete_cargo',
            'adjustments.delete_descargo',
            'adjustments.reject_cargo',
            'adjustments.reject_descargo',
            'transfers.create',
            'warehouses.index',
            'warehouses.create',
            'warehouses.edit',
            'warehouses.delete',
            
            // Users
            'users.index',
            'users.create',
            'users.edit',
            'users.delete',
            'users.edit_commercial_config',
            'users.edit_credit_config',
            'system.is_seller',
            'system.is_foreign_seller',
            
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
            'reports.financial',
            'reports.commissions',
            'reports.audit',
            'reports.customer_payment_relationship',
            'collections.audit',
            
            // Settings
            'settings.index',
            'settings.backups',
            'settings.logs',
            'settings.update',
            'settings.stock_reservation',
            
            // Production / Soplados
            'production.index',
            'production.create',
            'production.delete',
            'soplados.operator',
            'soplados.manager',

            // Distribution
            'distribution.map',
            'driver_monitoring',
        ];

        // Ensure permissions exist
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Clean up orphaned / legacy permissions not in the canonical list
        $orphaned = Permission::whereNotIn('name', $permissions)->get();
        foreach ($orphaned as $orphan) {
            $orphan->roles()->detach();
            $orphan->users()->detach();
            $orphan->delete();
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
