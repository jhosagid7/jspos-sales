<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Supplier::create([
            'name' => 'Proveedor General',
            'address' => 'Ciudad',
            'phone' => '555-1234',
        ]);

        Supplier::create([
            'name' => 'Distribuidora Electrónica',
            'address' => 'Av. Principal',
            'phone' => '555-5678',
        ]);
    }
}
