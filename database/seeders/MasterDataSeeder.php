<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Category;

class MasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds for a professional clean installation.
     * Includes only essential catalogs and master data.
     */
    public function run(): void
    {
        $this->call([
            // Core System Data
            CurrencySeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            
            // Initial Resources
            WarehouseSeeder::class,
            BankSeeder::class,
            
            // System Logic/Rules
            CashRegisterBypassSeeder::class,
            ConfigurationSeeder::class,
            
            // Extended Permissions
            CreatePermissionsSeeder::class,
            AddCustomerStatementPermissionSeeder::class,
            AddCommissionsPermissionsSeeder::class,
            AddPaymentConsultationPermissionsSeeder::class,
            AddSaleHistoryPermissionSeeder::class,
            AddDriverMonitoringPermissionSeeder::class,
            WarehousePermissionsSeeder::class,
            PriceListPermissionSeeder::class,
            ForeignSellerPermissionSeeder::class,
        ]);

        // Essential Records for immediate functionality
        if (Customer::count() === 0) {
            Customer::create([
                'name' => 'Consumidor Final',
                'address' => 'Ventas de Mostrador',
                'email' => 'final@cliente.com',
                'phone' => '00000000',
                'type' => 'Consumidor Final',
                'customer_commission_1_percentage' => 0,
                'customer_commission_1_threshold' => 0
            ]);
        }

        Category::firstOrCreate(['name' => 'General']);
    }
}
