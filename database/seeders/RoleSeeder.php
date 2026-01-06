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
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $sellerRole = Role::firstOrCreate(['name' => 'Vendedor']);

        // Assign all permissions to Admin
        $adminRole->syncPermissions(Permission::all());

        // Assign specific permissions to Vendedor
        $sellerPermissions = [
            'ventas',
            'guardar ordenes de ventas',
            'clientes',
            'corte-de-caja',
            'pago con Banco',
            'pago con credito',
            'pago con efectivo/nequi',
            'pago con Nequi',
            'productos', // To search products
            'inventarios', // To check stock
        ];

        $sellerRole->syncPermissions($sellerPermissions);
    }
}
