<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddTreasuryPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'treasury.index',     // Ver dashboard y reportes de tesorería
            'treasury.expenses',  // Crear/editar gastos y transferencias
            'treasury.closure',   // Hacer cortes diarios manualmente
            'treasury.config',    // Configurar bancos auditados y categorías
        ];

        foreach ($permissions as $permissionName) {
            if (!Permission::where('name', $permissionName)->exists()) {
                Permission::create(['name' => $permissionName, 'guard_name' => 'web']);
            }
        }

        // Asignar al Admin por defecto
        $role = Role::where('name', 'Admin')->first();
        if ($role) {
            foreach ($permissions as $permissionName) {
                if (!$role->hasPermissionTo($permissionName)) {
                    $role->givePermissionTo($permissionName);
                }
            }
        }
    }
}
