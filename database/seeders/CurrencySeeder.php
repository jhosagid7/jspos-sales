<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // USD - Primary
        Currency::create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'label' => 'Dólar Estadounidense',
            'symbol' => '$',
            'exchange_rate' => 1.00,
            'is_primary' => true,
        ]);

        // VED
        Currency::create([
            'code' => 'VED',
            'name' => 'Bolivar',
            'label' => 'Bolívar Venezolano',
            'symbol' => 'Bs',
            'exchange_rate' => 60.00, // Example rate, user can update
            'is_primary' => false,
        ]);

        // COP
        Currency::create([
            'code' => 'COP',
            'name' => 'Peso',
            'label' => 'Peso Colombiano',
            'symbol' => '$',
            'exchange_rate' => 4000.00, // Example rate, user can update
            'is_primary' => false,
        ]);
    }
}
