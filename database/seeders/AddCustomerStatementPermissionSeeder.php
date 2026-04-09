<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddCustomerStatementPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Define permissions
        $permissions = [
            'customer_statement.index',
            'customer_statement.view_all',
            'customer_statement.view_own',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // 2. Assign to elevated roles by default (Admin/Owner/Supervisor get everything)
        $adminRoles = Role::whereIn('name', ['Admin', 'Super Admin', 'Dueño', 'Supervisor'])->get();
        foreach ($adminRoles as $role) {
            $role->givePermissionTo($permissions);
        }

        // 3. Optional: Assign view_own to standard Sales roles if they exist
        $salesRoles = Role::whereIn('name', ['Vendedor', 'Vendedor Foraneo'])->get();
        foreach ($salesRoles as $role) {
             $role->givePermissionTo(['customer_statement.index', 'customer_statement.view_own']);
        }
    }
}
