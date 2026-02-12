<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CashRegisterBypassSeeder extends Seeder
{
    public function run()
    {
        // 1. Create permission
        $bypassPermission = Permission::firstOrCreate(['name' => 'cash_register.bypass']);
        
        // 2. Assign to 'Vendedor foraneo'
        $roleName = 'Vendedor foraneo';
        $role = Role::where('name', $roleName)->first();

        if ($role) {
            // Assign bypass
            $role->givePermissionTo($bypassPermission);
            $this->command->info("Assigned 'cash_register.bypass' to '$roleName'");

            // 3. Revoke Open/Close (undo previous fix)
            // They should NOT have these permissions if they are bypassing logic
            $role->revokePermissionTo('cash_register.open');
            $role->revokePermissionTo('cash_register.close');
            $this->command->info("Revoked 'cash_register.open/close' from '$roleName'");
        } else {
            $this->command->warn("Role '$roleName' not found.");
        }
    }
}
