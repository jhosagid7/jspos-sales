<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AddSaleHistoryPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissionName = 'sales.view_history';
        
        if (!Permission::where('name', $permissionName)->exists()) {
            Permission::create(['name' => $permissionName, 'guard_name' => 'web']);
        }

        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permissionName);
        }
    }
}
