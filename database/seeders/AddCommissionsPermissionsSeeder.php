<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddCommissionsPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'commissions.access',      // Ver el módulo y la tarjeta en dashboard
            'commissions.view_all',    // Ver comisiones de todos los vendedores
            'commissions.manage',      // Pagar y recalcular
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
