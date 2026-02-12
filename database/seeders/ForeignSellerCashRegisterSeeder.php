<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ForeignSellerCashRegisterSeeder extends Seeder
{
    public function run()
    {
        $roleName = 'Vendedor foraneo';
        $role = Role::where('name', $roleName)->first();

        if ($role) {
            // Permissions to add
            $permissions = ['cash_register.open', 'cash_register.close'];
            
            foreach ($permissions as $pName) {
                $permission = Permission::firstOrCreate(['name' => $pName]);
                $role->givePermissionTo($permission);
                $this->command->info("Assigned '$pName' to '$roleName'");
            }
        } else {
            $this->command->warn("Role '$roleName' not found.");
        }
    }
}
