<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Services\RoleTemplateService;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure Roles Exist
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin'], ['level' => 1000]); 
        $adminRole = Role::firstOrCreate(['name' => 'Admin'], ['level' => 100]);
        $ownerRole = Role::firstOrCreate(['name' => 'Dueño'], ['level' => 50]);
        $managerRole = Role::firstOrCreate(['name' => 'Administrador'], ['level' => 30]);
        $supervisorRole = Role::firstOrCreate(['name' => 'Supervisor'], ['level' => 20]);
        $operatorRole = Role::firstOrCreate(['name' => 'Operador'], ['level' => 10]);
        $cashierRole = Role::firstOrCreate(['name' => 'Cajero'], ['level' => 10]);
        $sellerRole = Role::firstOrCreate(['name' => 'Vendedor'], ['level' => 10]);
        $foreignSellerRole = Role::firstOrCreate(['name' => 'Vendedor Foraneo'], ['level' => 10]);
        $driverRole = Role::firstOrCreate(['name' => 'Driver'], ['level' => 10]);
        $choferRole = Role::firstOrCreate(['name' => 'Chofer'], ['level' => 10]);
        $sopladosRole = Role::firstOrCreate(['name' => 'Operario Soplados'], ['level' => 10]);

        // Update levels
        $superAdminRole->update(['level' => 1000]);
        $adminRole->update(['level' => 100]);
        $ownerRole->update(['level' => 50]);
        $managerRole->update(['level' => 30]);
        $supervisorRole->update(['level' => 20]);
        $operatorRole->update(['level' => 10]);
        $cashierRole->update(['level' => 10]);
        $sellerRole->update(['level' => 10]);
        $foreignSellerRole->update(['level' => 10]);
        $driverRole->update(['level' => 10]);
        $choferRole->update(['level' => 10]);
        $sopladosRole->update(['level' => 10]);

        // 2. Assign Permissions using RoleTemplateService
        RoleTemplateService::applyTemplateToRole($superAdminRole, 'super_admin');
        RoleTemplateService::applyTemplateToRole($adminRole, 'super_admin');
        RoleTemplateService::applyTemplateToRole($ownerRole, 'admin');
        RoleTemplateService::applyTemplateToRole($managerRole, 'admin');
        RoleTemplateService::applyTemplateToRole($supervisorRole, 'supervisor');
        RoleTemplateService::applyTemplateToRole($operatorRole, 'cashier');
        RoleTemplateService::applyTemplateToRole($cashierRole, 'cashier');
        RoleTemplateService::applyTemplateToRole($sellerRole, 'seller');
        RoleTemplateService::applyTemplateToRole($foreignSellerRole, 'foreign_seller');
        RoleTemplateService::applyTemplateToRole($driverRole, 'driver');
        RoleTemplateService::applyTemplateToRole($choferRole, 'driver');
        RoleTemplateService::applyTemplateToRole($sopladosRole, 'soplados');
    }
}
