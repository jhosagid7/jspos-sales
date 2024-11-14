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
        $adminRole = Role::where('name', 'Admin')->first();

        if (!$adminRole) {
            $adminRole = Role::create(['name' => 'Admin']);
        }

        // create permissions
        $permissionsToCreate = [
            ['name' => 'asignacion', 'guard_name' => 'web'],
            ['name' => 'catalogos', 'guard_name' => 'web'],
            ['name' => 'categorias', 'guard_name' => 'web'],
            ['name' => 'clientes', 'guard_name' => 'web'],
            ['name' => 'compras', 'guard_name' => 'web'],
            ['name' => 'corte-de-caja', 'guard_name' => 'web'],
            ['name' => 'guardar ordenes de ventas', 'guard_name' => 'web'],
            ['name' => 'inventarios', 'guard_name' => 'web'],
            ['name' => 'metodos de pago', 'guard_name' => 'web'],
            ['name' => 'pago con Banco', 'guard_name' => 'web'],
            ['name' => 'pago con credito', 'guard_name' => 'web'],
            ['name' => 'pago con efectivo/nequi', 'guard_name' => 'web'],
            ['name' => 'pago con Nequi', 'guard_name' => 'web'],
            ['name' => 'personal', 'guard_name' => 'web'],
            ['name' => 'productos', 'guard_name' => 'web'],
            ['name' => 'proveedores', 'guard_name' => 'web'],
            ['name' => 'reporte-compras', 'guard_name' => 'web'],
            ['name' => 'reporte-cuentas-cobrar', 'guard_name' => 'web'],
            ['name' => 'reporte-cuentas-pagar', 'guard_name' => 'web'],
            ['name' => 'reporte-ventas', 'guard_name' => 'web'],
            ['name' => 'reportes', 'guard_name' => 'web'],
            ['name' => 'roles', 'guard_name' => 'web'],
            ['name' => 'settings', 'guard_name' => 'web'],
            ['name' => 'usuarios', 'guard_name' => 'web'],
            ['name' => 'ventas', 'guard_name' => 'web'],
        ];

        foreach ($permissionsToCreate as $permission) {
            if (!Permission::where('name', $permission['name'])->exists()) {
                Permission::create($permission);
            }
        }

        // sync permissions to admin
        if ($adminRole && $permissionsToCreate) {
            $adminRole->syncPermissions($permissionsToCreate);
        }

        // Asignar el rol de Admin al usuario creado
        if ($adminUser && $adminRole) {
            $adminUser->assignRole($adminRole);
        }
    }
}
