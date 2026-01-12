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
        // Create Permissions
        $permissions = [
            'asignacion',
            'catalogos',
            'categorias',
            'clientes',
            'compras',
            'corte-de-caja',
            'guardar ordenes de ventas',
            'inventarios',
            'metodos de pago',
            'pago con Banco',
            'pago con credito',
            'pago con efectivo/nequi',
            'pago con Nequi',
            'personal',
            'productos',
            'proveedores',
            'reporte-compras',
            'reporte-cuentas-cobrar',
            'reporte-cuentas-pagar',
            'reporte-ventas',
            'reportes',
            'roles',
            'settings',
            'usuarios',
            'ventas',
            'gestionar_comisiones',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create Roles
        // Create Roles with Levels
        $adminRole = Role::firstOrCreate(['name' => 'Admin'], ['level' => 100]);
        $ownerRole = Role::firstOrCreate(['name' => 'Dueño'], ['level' => 50]);
        $managerRole = Role::firstOrCreate(['name' => 'Administrador'], ['level' => 30]);
        $operatorRole = Role::firstOrCreate(['name' => 'Operador'], ['level' => 10]);
        $cashierRole = Role::firstOrCreate(['name' => 'Cajero'], ['level' => 10]);
        $sellerRole = Role::firstOrCreate(['name' => 'Vendedor'], ['level' => 10]);
        
        // Update levels if roles already exist
        $adminRole->update(['level' => 100]);
        $ownerRole->update(['level' => 50]);
        $managerRole->update(['level' => 30]);
        $operatorRole->update(['level' => 10]);
        $cashierRole->update(['level' => 10]);
        $sellerRole->update(['level' => 10]);

        // Assign all permissions to Admin
        $adminRole->syncPermissions(Permission::all());

        // Dueño: All permissions except 'roles', 'asignacion', 'settings'
        // User explicitly stated Dueño cannot manage roles or permissions.
        $ownerPermissions = Permission::whereNotIn('name', ['roles', 'asignacion', 'settings'])->get();
        $ownerRole->syncPermissions($ownerPermissions);

        // Administrador: Manage users (limited), reports, products, inventory, sales
        $managerPermissions = [
            'usuarios',
            'personal',
            'clientes',
            'proveedores',
            'productos',
            'categorias',
            'inventarios',
            'compras',
            'ventas',
            'guardar ordenes de ventas',
            'corte-de-caja',
            'reportes',
            'reporte-ventas',
            'reporte-compras',
            'reporte-cuentas-cobrar',
            'reporte-cuentas-pagar',
            'pago con Banco',
            'pago con credito',
            'pago con efectivo/nequi',
            'pago con Nequi',
            'gestionar_comisiones'
        ];
        $managerRole->syncPermissions($managerPermissions);

        // Operador / Cajero / Vendedor: Sales and basic operations
        $operationalPermissions = [
            'ventas',
            'guardar ordenes de ventas',
            'clientes',
            'corte-de-caja',
            'pago con Banco',
            'pago con credito',
            'pago con efectivo/nequi',
            'pago con Nequi',
            'productos', // To search
            'inventarios', // To check stock
        ];

        $operatorRole->syncPermissions($operationalPermissions);
        $cashierRole->syncPermissions($operationalPermissions);
        $sellerRole->syncPermissions($operationalPermissions);
    }
}
