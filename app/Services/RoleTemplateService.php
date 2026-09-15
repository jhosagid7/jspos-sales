<?php

namespace App\Services;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleTemplateService
{
    /**
     * Get all available role templates with metadata and permissions.
     */
    public static function getTemplates(): array
    {
        return [
            'super_admin' => [
                'name' => 'Super Administrador',
                'badge' => 'Total',
                'color' => 'danger',
                'icon' => 'shield-lock',
                'description' => 'Acceso absoluto e ilimitado a todos los módulos y configuraciones del sistema.',
                'permissions' => ['*'], // All permissions
            ],
            'admin' => [
                'name' => 'Administrador',
                'badge' => 'Gestión Total',
                'color' => 'primary',
                'icon' => 'person-gear',
                'description' => 'Gestión completa de ventas, compras, inventario, usuarios, clientes y reportes.',
                'permissions' => self::getAdminPermissions(),
            ],
            'supervisor' => [
                'name' => 'Supervisor',
                'badge' => 'Control & Auditoría',
                'color' => 'warning',
                'icon' => 'person-check',
                'description' => 'Autorización de descuentos, créditos, cargos/descargos, anulación de pagos y reportes.',
                'permissions' => self::getSupervisorPermissions(),
            ],
            'cashier' => [
                'name' => 'Cajero / Operador',
                'badge' => 'Punto de Venta',
                'color' => 'success',
                'icon' => 'cash-coin',
                'description' => 'Ventas POS completas, apertura/cierre de caja, cobro multimoneda, tickets e impresión.',
                'permissions' => self::getCashierPermissions(),
            ],
            'seller' => [
                'name' => 'Vendedor Local',
                'badge' => 'Mostrador / Preventa',
                'color' => 'info',
                'icon' => 'cart-check',
                'description' => 'Ventas POS, pedidos, clientes propios, consulta de stock y subida de comprobantes.',
                'permissions' => self::getSellerPermissions(),
            ],
            'foreign_seller' => [
                'name' => 'Vendedor Foráneo (Ruta)',
                'badge' => 'Móvil / Bypass Caja',
                'color' => 'secondary',
                'icon' => 'geo-alt',
                'description' => 'Toma de pedidos en ruta con bypass de caja física, clientes propios y catálogo móvil.',
                'permissions' => self::getForeignSellerPermissions(),
            ],
            'driver' => [
                'name' => 'Chofer / Repartidor',
                'badge' => 'Despacho & Rutas',
                'color' => 'dark',
                'icon' => 'truck',
                'description' => 'Mapa de distribución, monitoreo de rutas, órdenes asignadas y direcciones de clientes.',
                'permissions' => self::getDriverPermissions(),
            ],
            'soplados' => [
                'name' => 'Operario Soplados',
                'badge' => 'Manufactura',
                'color' => 'cyan',
                'icon' => 'gear-wide-connected',
                'description' => 'Registro de producción de botellones y turnos de planta de soplados.',
                'permissions' => [
                    'production.index',
                    'production.create',
                    'soplados.operator',
                ],
            ],
        ];
    }

    public static function getAdminPermissions(): array
    {
        return [
            // Sales & POS
            'sales.index', 'sales.create', 'sales.edit', 'sales.pdf', 'sales.view_all', 'sales.view_history',
            'sales.manage_adjustments', 'sales.show_exchange_rate', 'sales.change_invoice_currency',
            'sales.mix_warehouses', 'sales.switch_warehouse', 'sales.select_driver', 'sales.configure_price_list',
            'sales.generate_price_list', 'pos.select_operator', 'manage_debit_notes',
            // Orders
            'orders.view_all', 'orders.view_history', 'orders.save', 'orders.add_to_cart', 'orders.delete',
            'orders.edit', 'orders.details', 'orders.pdf',
            // Payments
            'payments.view_all', 'payments.pay', 'payments.history', 'payments.print_receipt', 'payments.view_proof',
            'payments.print_history', 'payments.print_pdf', 'payments.upload', 'payments.approve', 'payments.register_direct',
            'payments.delete', 'payments.void_today', 'payments.approve_custom_rate', 'payments.force_discounts',
            'payments.methods', 'payments.method_cash', 'payments.method_bank', 'payments.method_credit', 'payments.method_nequi',
            // Cash Register
            'cash_register.open', 'cash_register.close', 'cash_register.access', 'cash_register.view_all', 'cash_register.bypass',
            // Products & Categories
            'products.index', 'products.create', 'products.edit', 'products.delete', 'products.import', 'products.labels',
            'products.edit.inventory', 'products.edit.categories', 'products.edit.price_rules',
            'categories.index', 'categories.create', 'categories.edit', 'categories.delete',
            // Customers
            'customers.index', 'customers.create', 'customers.edit', 'customers.delete', 'customers.import', 'customers.view_all',
            'customers.edit_commercial_config', 'customers.edit_credit_config',
            'customer_statement.index', 'customer_statement.view_all',
            // Suppliers & Purchases
            'suppliers.index', 'suppliers.create', 'suppliers.edit', 'suppliers.delete',
            'purchases.index', 'purchases.create', 'purchases.edit', 'purchases.delete',
            // Inventory & Warehouses
            'inventory.index', 'adjustments.create', 'adjustments.approve', 'adjustments.approve_cargo', 'adjustments.approve_descargo',
            'adjustments.delete_cargo', 'adjustments.delete_descargo', 'adjustments.reject_cargo', 'adjustments.reject_descargo',
            'transfers.create', 'warehouses.index', 'warehouses.create', 'warehouses.edit',
            // Users
            'users.index', 'users.create', 'users.edit', 'users.edit_commercial_config', 'users.edit_credit_config',
            // Reports
            'reports.sales', 'reports.purchases', 'reports.stock', 'reports.financial', 'reports.commissions',
            'reports.audit', 'reports.customer_payment_relationship', 'collections.audit',
            // Settings
            'settings.index', 'settings.stock_reservation',
            // Production & Distribution
            'production.index', 'production.create', 'soplados.manager',
            'distribution.map', 'driver_monitoring',
        ];
    }

    public static function getSupervisorPermissions(): array
    {
        return [
            // Sales view and authorizations
            'sales.index', 'sales.pdf', 'sales.view_all', 'sales.view_history', 'sales.approve_deletion',
            'sales.manage_adjustments', 'sales.show_exchange_rate', 'sales.mix_warehouses', 'sales.switch_warehouse',
            'pos.select_operator', 'manage_debit_notes',
            // Orders
            'orders.view_all', 'orders.view_history', 'orders.details', 'orders.pdf',
            // Payments and Approvals
            'payments.view_all', 'payments.history', 'payments.print_receipt', 'payments.view_proof', 'payments.print_history',
            'payments.print_pdf', 'payments.approve', 'payments.void_today', 'payments.void_anytime',
            'payments.approve_custom_rate', 'payments.force_discounts',
            // Cash Register
            'cash_register.access', 'cash_register.view_all',
            // Products and Customers Consultation
            'products.index', 'categories.index', 'customers.index', 'customers.view_all',
            'customer_statement.index', 'customer_statement.view_all',
            // Adjustments Approvals
            'inventory.index', 'adjustments.approve', 'adjustments.approve_cargo', 'adjustments.approve_descargo',
            'adjustments.reject_cargo', 'adjustments.reject_descargo', 'warehouses.index',
            // Reports & Audits
            'reports.sales', 'reports.stock', 'reports.financial', 'reports.commissions', 'reports.audit',
            'reports.customer_payment_relationship', 'collections.audit',
        ];
    }

    public static function getCashierPermissions(): array
    {
        return [
            // POS Sales
            'sales.index', 'sales.create', 'sales.pdf', 'sales.show_exchange_rate', 'pos.select_operator', 'manage_debit_notes',
            // Cash Register
            'cash_register.open', 'cash_register.close', 'cash_register.access', 'cash_register.view_own',
            // Orders
            'orders.view_all', 'orders.save', 'orders.add_to_cart', 'orders.edit', 'orders.details', 'orders.pdf',
            // Customers
            'customers.index', 'customers.create', 'customers.edit', 'customers.view_all',
            'customer_statement.index', 'customer_statement.view_all',
            // Products & Stock
            'products.index', 'inventory.index',
            // Payments
            'payments.view_all', 'payments.pay', 'payments.history', 'payments.print_receipt', 'payments.view_proof',
            'payments.print_history', 'payments.print_pdf', 'payments.register_direct',
            'payments.methods', 'payments.method_cash', 'payments.method_bank', 'payments.method_credit', 'payments.method_nequi',
        ];
    }

    public static function getSellerPermissions(): array
    {
        return [
            // System Role
            'system.is_seller',
            // Sales & Orders
            'sales.index', 'sales.create', 'sales.pdf', 'sales.view_own', 'sales.view_history',
            'cash_register.open', 'cash_register.close', 'cash_register.access', 'cash_register.view_own',
            'orders.view_own', 'orders.view_history', 'orders.save', 'orders.add_to_cart', 'orders.edit', 'orders.details', 'orders.pdf',
            // Customers
            'customers.index', 'customers.create', 'customers.edit', 'customers.view_own',
            'customer_statement.index', 'customer_statement.view_own',
            // Products
            'products.index', 'inventory.index',
            // Payments / Comprobantes
            'payments.view_own', 'payments.pay', 'payments.history', 'payments.print_receipt', 'payments.view_proof',
            'payments.print_history', 'payments.print_pdf', 'payments.upload',
            'payments.methods', 'payments.method_cash', 'payments.method_bank', 'payments.method_credit', 'payments.method_nequi',
        ];
    }

    public static function getForeignSellerPermissions(): array
    {
        return [
            // System Role Flags
            'system.is_seller', 'system.is_foreign_seller',
            // Cash Register Bypass (No physical cash register needed)
            'cash_register.bypass', 'cash_register.access',
            // Sales & Orders
            'sales.index', 'sales.create', 'sales.pdf', 'sales.view_own', 'sales.view_history',
            'orders.view_own', 'orders.view_history', 'orders.save', 'orders.add_to_cart', 'orders.edit', 'orders.details', 'orders.pdf',
            // Customers
            'customers.index', 'customers.create', 'customers.edit', 'customers.view_own',
            'customer_statement.index', 'customer_statement.view_own',
            // Products
            'products.index', 'inventory.index',
            // Payments / Comprobantes
            'payments.view_own', 'payments.pay', 'payments.history', 'payments.print_receipt', 'payments.view_proof',
            'payments.print_history', 'payments.print_pdf', 'payments.upload',
            'payments.methods', 'payments.method_cash', 'payments.method_bank', 'payments.method_credit', 'payments.method_nequi',
        ];
    }

    public static function getDriverPermissions(): array
    {
        return [
            // Distribution & Tracking
            'distribution.map', 'driver_monitoring',
            // Orders & Sales Viewing
            'sales.index', 'sales.pdf', 'sales.select_driver', 'sales.view_all',
            'orders.view_all', 'orders.details', 'orders.pdf',
            'customers.index', 'customers.view_all',
        ];
    }

    /**
     * Apply a specific template to a role by name or ID.
     */
    public static function applyTemplateToRole(Role $role, string $templateKey): array
    {
        $templates = self::getTemplates();
        if (!isset($templates[$templateKey])) {
            throw new \InvalidArgumentException("La plantilla '{$templateKey}' no existe.");
        }

        $template = $templates[$templateKey];
        $permList = $template['permissions'];

        if (in_array('*', $permList)) {
            $permissions = Permission::all();
        } else {
            $permissions = Permission::whereIn('name', $permList)->get();
        }

        $role->syncPermissions($permissions);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return [
            'template_name' => $template['name'],
            'permissions_count' => $permissions->count(),
        ];
    }
}
