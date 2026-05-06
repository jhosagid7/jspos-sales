<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddGranularConfigPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'customers.edit_commercial_config',
            'customers.edit_credit_config',
            'users.edit_commercial_config',
            'users.edit_credit_config',
        ];

        foreach ($permissions as $permissionName) {
            if (!Permission::where('name', $permissionName)->exists()) {
                Permission::create(['name' => $permissionName, 'guard_name' => 'web']);
            }
        }

        // Assign to Admin and Super Admin roles by default
        $roles = Role::whereIn('name', ['Admin', 'Super Admin', 'Dueño', 'Administrador'])->get();
        foreach ($roles as $role) {
            $role->givePermissionTo($permissions);
        }
    }
}
