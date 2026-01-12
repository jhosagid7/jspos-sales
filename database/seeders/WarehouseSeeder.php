<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Warehouse::firstOrCreate([
            'name' => 'Tienda Principal',
            'address' => 'Dirección Principal',
            'is_active' => true
        ]);
    }
}
