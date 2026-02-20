<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddPaymentConsultationPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Zelle Permissions
        Permission::firstOrCreate(['name' => 'zelle_index', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'zelle_view_details', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'zelle_print_pdf', 'guard_name' => 'web']);

        // Bank Permissions
        Permission::firstOrCreate(['name' => 'bank_index', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'bank_view_details', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'bank_print_pdf', 'guard_name' => 'web']);

        // Assign to Admin and Super Admin by default
        $roles = Role::whereIn('name', ['Admin', 'Super Admin', 'Dueño'])->get();
        
        $permissions = [
            'zelle_index', 'zelle_view_details', 'zelle_print_pdf',
            'bank_index', 'bank_view_details', 'bank_print_pdf'
        ];

        foreach ($roles as $role) {
            $role->givePermissionTo($permissions);
        }
    }
}
