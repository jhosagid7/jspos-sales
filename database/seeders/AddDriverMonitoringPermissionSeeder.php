<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddDriverMonitoringPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Permission to monitor other drivers' routes
        $permission = Permission::firstOrCreate(['name' => 'driver_monitoring', 'guard_name' => 'web']);

        // Assign to Admin and Supervisor by default
        $roles = ['Admin', 'Supervisor'];
        foreach ($roles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($permission);
            }
        }
    }
}
