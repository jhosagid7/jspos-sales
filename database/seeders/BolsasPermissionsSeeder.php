<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class BolsasPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Define Bolsas Permissions
        $permissions = [
            ['name' => 'bolsas.operator', 'description' => 'Acceso a App Bolsas y registro de producción'],
            ['name' => 'bolsas.manager', 'description' => 'Gestionar producción y logística de bolsas'],
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p['name']], ['guard_name' => 'web']);
        }

        // Auto-assign to Admin and Super Admin
        $adminRoles = Role::whereIn('name', ['Admin', 'Super Admin'])->get();
        foreach ($adminRoles as $role) {
            $role->givePermissionTo(['bolsas.operator', 'bolsas.manager']);
        }

        // Create a specific "Bolsas" role
        $factoryRole = Role::firstOrCreate(['name' => 'Operario Bolsas'], ['level' => 10, 'guard_name' => 'web']);
        $factoryRole->givePermissionTo('bolsas.operator');
    }
}
