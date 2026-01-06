<?php

namespace Database\Seeders;

use App\Models\Configuration;
use Illuminate\Database\Seeder;

class ConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Configuration::create([
            'business_name' => 'IT COMPANY',
            'address' => 'VENEZUELA',
            'phone' => '5555555',
            'taxpayer_id' => 'RUT123456',
            'vat' => 0,
            'printer_name' => '80mm',
            'leyend' => 'Gracias por su compra!',
            'website' => 'https://jhonnypirela.dev',
            'credit_days' => 30,
            'credit_purchase_days' => 30,
            'confirmation_code' => 7,
            'global_commission_1_threshold' => 15,
            'global_commission_1_percentage' => 8.00,
            'global_commission_2_threshold' => 30,
            'global_commission_2_percentage' => 4.00,
        ]);
    }
}
