<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SopladosPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Define Soplados Permissions
        $permissions = [
            ['name' => 'soplados.operator', 'description' => 'Acceso a App Soplados y registro de producción'],
            ['name' => 'soplados.manager', 'description' => 'Aprobar transferencias y gestionar recetas de soplados'],
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p['name']], ['guard_name' => 'web']);
        }

        // Auto-assign to Admin and Super Admin
        $adminRoles = Role::whereIn('name', ['Admin', 'Super Admin'])->get();
        foreach ($adminRoles as $role) {
            $role->givePermissionTo(['soplados.operator', 'soplados.manager']);
        }

        // Create a specific "Soplados" role for factory workers if it doesn't exist
        $factoryRole = Role::firstOrCreate(['name' => 'Operario Soplados'], ['level' => 10, 'guard_name' => 'web']);
        $factoryRole->givePermissionTo('soplados.operator');
    }
}
