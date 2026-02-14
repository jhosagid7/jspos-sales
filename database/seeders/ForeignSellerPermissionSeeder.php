<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ForeignSellerPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Create the new permission
        $permissionName = 'system.is_foreign_seller';
        $permission = Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        
        $this->command->info("Permission '$permissionName' created/verified.");

        // 2. Assign to 'Vendedor foraneo' role
        $roleName = 'Vendedor foraneo';
        $role = Role::where('name', $roleName)->first();

        if ($role) {
            $role->givePermissionTo($permission);
            $this->command->info("Permission '$permissionName' assigned to role '$roleName'.");
        } else {
            $this->command->warn("Role '$roleName' not found. Permission created but not assigned to it.");
        }
        
        // 3. Optional: Assign to 'Vendedor' role if they should also be considered "Foreign" in this context?
        // Based on user request, we want to distinguish. "Vendedor" (Office Seller) usually DOES NOT need this if they assign to OFICINA.
        // So we ONLY assign to 'Vendedor foraneo'.
    }
}
