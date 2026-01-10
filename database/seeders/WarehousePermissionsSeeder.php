<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WarehousePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'sales.switch_warehouse',
            'sales.mix_warehouses',
            'warehouses.create',
            'warehouses.edit',
            'warehouses.delete'
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Assign to Admin role
        $role = \Spatie\Permission\Models\Role::where('name', 'Admin')->first();
        if ($role) {
            $role->givePermissionTo($permissions);
        }
    }
}
