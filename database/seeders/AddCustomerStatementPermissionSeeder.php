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
        // 1. Create permission
        Permission::firstOrCreate(['name' => 'customer_statement.index', 'guard_name' => 'web']);

        // 2. Assign to elevated roles by default
        $roles = Role::whereIn('name', ['Admin', 'Super Admin', 'Dueño', 'Supervisor'])->get();
        
        foreach ($roles as $role) {
            $role->givePermissionTo('customer_statement.index');
        }
    }
}
