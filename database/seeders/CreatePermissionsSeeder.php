<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CreatePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // test user
        $adminUser = User::where('email', 'jhosagid7@gmail.com')->first();

        if (!$adminUser) {
            $adminUser = User::create([
                'name' => 'Jhonny Pirela',
                'email' => 'jhosagid7@gmail.com',
                'password' => bcrypt('jhosagid'),
                'profile' => 'Admin'
            ]);
        }

        // test role
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);

        // create permissions
        $permissionsToCreate = [
            // Verified list of 125 permissions (2026-02-17)
            ['name' => 'adjustments.approve', 'guard_name' => 'web'],
            ['name' => 'adjustments.create', 'guard_name' => 'web'],
            ['name' => 'aprobar_cargos', 'guard_name' => 'web'],
            ['name' => 'aprobar_descargos', 'guard_name' => 'web'],
            ['name' => 'asignacion', 'guard_name' => 'web'],
            ['name' => 'cash_register.access', 'guard_name' => 'web'],
            ['name' => 'cash_register.bypass', 'guard_name' => 'web'],
            ['name' => 'cash_register.close', 'guard_name' => 'web'],
            ['name' => 'cash_register.open', 'guard_name' => 'web'],
            ['name' => 'cash_register.view_all', 'guard_name' => 'web'],
            ['name' => 'cash_register.view_own', 'guard_name' => 'web'],
            ['name' => 'catalogos', 'guard_name' => 'web'],
            ['name' => 'categorias', 'guard_name' => 'web'],
            ['name' => 'categories.create', 'guard_name' => 'web'],
            ['name' => 'categories.delete', 'guard_name' => 'web'],
            ['name' => 'categories.edit', 'guard_name' => 'web'],
            ['name' => 'categories.index', 'guard_name' => 'web'],
            ['name' => 'clientes', 'guard_name' => 'web'],
            ['name' => 'compras', 'guard_name' => 'web'],
            ['name' => 'corte-de-caja', 'guard_name' => 'web'],
            ['name' => 'customers.create', 'guard_name' => 'web'],
            ['name' => 'customers.delete', 'guard_name' => 'web'],
            ['name' => 'customers.edit', 'guard_name' => 'web'],
            ['name' => 'customers.index', 'guard_name' => 'web'],
            ['name' => 'customers.view_all', 'guard_name' => 'web'],
            ['name' => 'customers.view_own', 'guard_name' => 'web'],
            ['name' => 'distribution.map', 'guard_name' => 'web'],
            ['name' => 'gestionar_comisiones', 'guard_name' => 'web'],
            ['name' => 'guardar ordenes de ventas', 'guard_name' => 'web'],
            ['name' => 'inventarios', 'guard_name' => 'web'],
            ['name' => 'inventory.index', 'guard_name' => 'web'],
            ['name' => 'manage_production', 'guard_name' => 'web'],
            ['name' => 'metodos de pago', 'guard_name' => 'web'],
            ['name' => 'orders.add_to_cart', 'guard_name' => 'web'],
            ['name' => 'orders.delete', 'guard_name' => 'web'],
            ['name' => 'orders.details', 'guard_name' => 'web'],
            ['name' => 'orders.edit', 'guard_name' => 'web'],
            ['name' => 'orders.pdf', 'guard_name' => 'web'],
            ['name' => 'orders.view_all', 'guard_name' => 'web'],
            ['name' => 'orders.view_own', 'guard_name' => 'web'],
            ['name' => 'pago con Banco', 'guard_name' => 'web'],
            ['name' => 'pago con credito', 'guard_name' => 'web'],
            ['name' => 'pago con efectivo/nequi', 'guard_name' => 'web'],
            ['name' => 'pago con Nequi', 'guard_name' => 'web'],
            ['name' => 'payments.approve', 'guard_name' => 'web'],
            ['name' => 'payments.delete', 'guard_name' => 'web'],
            ['name' => 'payments.force_discounts', 'guard_name' => 'web'],
            ['name' => 'payments.history', 'guard_name' => 'web'],
            ['name' => 'payments.pay', 'guard_name' => 'web'],
            ['name' => 'payments.print_history', 'guard_name' => 'web'],
            ['name' => 'payments.print_pdf', 'guard_name' => 'web'],
            ['name' => 'payments.print_receipt', 'guard_name' => 'web'],
            ['name' => 'payments.register_direct', 'guard_name' => 'web'],
            ['name' => 'payments.upload', 'guard_name' => 'web'],
            ['name' => 'payments.view_all', 'guard_name' => 'web'],
            ['name' => 'payments.view_own', 'guard_name' => 'web'],
            ['name' => 'payments.view_proof', 'guard_name' => 'web'],
            ['name' => 'permissions.assign', 'guard_name' => 'web'],
            ['name' => 'personal', 'guard_name' => 'web'],
            ['name' => 'production.create', 'guard_name' => 'web'],
            ['name' => 'production.delete', 'guard_name' => 'web'],
            ['name' => 'production.index', 'guard_name' => 'web'],
            ['name' => 'productos', 'guard_name' => 'web'],
            ['name' => 'products.create', 'guard_name' => 'web'],
            ['name' => 'products.delete', 'guard_name' => 'web'],
            ['name' => 'products.edit', 'guard_name' => 'web'],
            ['name' => 'products.import', 'guard_name' => 'web'],
            ['name' => 'products.index', 'guard_name' => 'web'],
            ['name' => 'products.labels', 'guard_name' => 'web'],
            ['name' => 'proveedores', 'guard_name' => 'web'],
            ['name' => 'purchases.create', 'guard_name' => 'web'],
            ['name' => 'purchases.delete', 'guard_name' => 'web'],
            ['name' => 'purchases.edit', 'guard_name' => 'web'],
            ['name' => 'purchases.index', 'guard_name' => 'web'],
            ['name' => 'reporte-compras', 'guard_name' => 'web'],
            ['name' => 'reporte-cuentas-cobrar', 'guard_name' => 'web'],
            ['name' => 'reporte-cuentas-pagar', 'guard_name' => 'web'],
            ['name' => 'reporte-ventas', 'guard_name' => 'web'],
            ['name' => 'reportes', 'guard_name' => 'web'],
            ['name' => 'reports.commissions', 'guard_name' => 'web'],
            ['name' => 'reports.financial', 'guard_name' => 'web'],
            ['name' => 'reports.purchases', 'guard_name' => 'web'],
            ['name' => 'reports.sales', 'guard_name' => 'web'],
            ['name' => 'reports.stock', 'guard_name' => 'web'],
            ['name' => 'roles', 'guard_name' => 'web'],
            ['name' => 'roles.create', 'guard_name' => 'web'],
            ['name' => 'roles.delete', 'guard_name' => 'web'],
            ['name' => 'roles.edit', 'guard_name' => 'web'],
            ['name' => 'roles.index', 'guard_name' => 'web'],
            ['name' => 'sales.approve_deletion', 'guard_name' => 'web'],
            ['name' => 'sales.change_invoice_currency', 'guard_name' => 'web'],
            ['name' => 'sales.configure_price_list', 'guard_name' => 'web'],
            ['name' => 'sales.create', 'guard_name' => 'web'],
            ['name' => 'sales.delete', 'guard_name' => 'web'],
            ['name' => 'sales.edit', 'guard_name' => 'web'],
            ['name' => 'sales.generate_price_list', 'guard_name' => 'web'],
            ['name' => 'sales.index', 'guard_name' => 'web'],
            ['name' => 'sales.manage_adjustments', 'guard_name' => 'web'],
            ['name' => 'sales.mix_warehouses', 'guard_name' => 'web'],
            ['name' => 'sales.pdf', 'guard_name' => 'web'],
            ['name' => 'sales.switch_warehouse', 'guard_name' => 'web'],
            ['name' => 'sales.view_all', 'guard_name' => 'web'],
            ['name' => 'sales.view_own', 'guard_name' => 'web'],
            ['name' => 'sales.reset_credit_snapshot', 'guard_name' => 'web'],
            ['name' => 'settings', 'guard_name' => 'web'],
            ['name' => 'settings.backups', 'guard_name' => 'web'],
            ['name' => 'settings.index', 'guard_name' => 'web'],
            ['name' => 'settings.logs', 'guard_name' => 'web'],
            ['name' => 'settings.stock_reservation', 'guard_name' => 'web'],
            ['name' => 'settings.update', 'guard_name' => 'web'],
            ['name' => 'suppliers.create', 'guard_name' => 'web'],
            ['name' => 'suppliers.delete', 'guard_name' => 'web'],
            ['name' => 'suppliers.edit', 'guard_name' => 'web'],
            ['name' => 'suppliers.index', 'guard_name' => 'web'],
            ['name' => 'system.is_foreign_seller', 'guard_name' => 'web'],
            ['name' => 'system.is_seller', 'guard_name' => 'web'],
            ['name' => 'transfers.create', 'guard_name' => 'web'],
            ['name' => 'users.create', 'guard_name' => 'web'],
            ['name' => 'users.delete', 'guard_name' => 'web'],
            ['name' => 'users.edit', 'guard_name' => 'web'],
            ['name' => 'users.index', 'guard_name' => 'web'],
            ['name' => 'usuarios', 'guard_name' => 'web'],
            ['name' => 'ventas', 'guard_name' => 'web'],
            ['name' => 'warehouses.create', 'guard_name' => 'web'],
            ['name' => 'warehouses.delete', 'guard_name' => 'web'],
            ['name' => 'warehouses.edit', 'guard_name' => 'web'],
            ['name' => 'warehouses.index', 'guard_name' => 'web'],
        ];

        foreach ($permissionsToCreate as $permission) {
            if (!Permission::where('name', $permission['name'])->exists()) {
                Permission::create($permission);
            }
        }

        // sync permissions to admin
        if ($adminRole && $permissionsToCreate) {
             $permissionNames = array_column($permissionsToCreate, 'name');
             $adminRole->givePermissionTo($permissionNames);
        }

        // Asignar el rol de Admin al usuario creado
        if ($adminUser && $adminRole) {
            $adminUser->assignRole($adminRole);
        }
    }
}
