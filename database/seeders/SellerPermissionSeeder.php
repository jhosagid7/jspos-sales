<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SellerPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Create the new permission
        $permissionName = 'system.is_seller';
        
        $permission = Permission::firstOrCreate(['name' => $permissionName]);

        // 2. Assign to 'Vendedor' role (if exists)
        $roleName = 'Vendedor';
        $role = Role::where('name', $roleName)->first();

        if ($role) {
            $role->givePermissionTo($permission);
            $this->command->info("Permission '$permissionName' assigned to role '$roleName'.");
        } else {
            $this->command->warn("Role '$roleName' not found. Permission created but not assigned.");
        }
        
        // 3. Optional: Assign to 'Admin' or 'Super Admin' if they should also have it? 
        // Usually Admin doesn't need "seller config" because they are Admin, but for consistency:
        // Let's stick to user request: "any role that has this permission is a seller"
    }
}
