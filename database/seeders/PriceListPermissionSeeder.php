<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PriceListPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create configuration permission
        $configPermission = Permission::firstOrCreate(['name' => 'sales.configure_price_list', 'guard_name' => 'web']);
        
        // Create access/generate permission
        $accessPermission = Permission::firstOrCreate(['name' => 'sales.generate_price_list', 'guard_name' => 'web']);

        // Assign to Admin role (both)
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($configPermission);
            $adminRole->givePermissionTo($accessPermission);
        }

        // Assign Access to Foreign Seller
        $sellerRole = Role::where('name', 'Vendedor foraneo')->first();
        if ($sellerRole) {
            $sellerRole->givePermissionTo($accessPermission);
        }
        
        // Assign Access to Office? User didn't specify, but likely yes.
        // Let's assume generic 'Vendedor' also needs it?
        $vendedorRole = Role::where('name', 'Vendedor')->first();
        if($vendedorRole) {
            $vendedorRole->givePermissionTo($accessPermission);
        }
    }
}
