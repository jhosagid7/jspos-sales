<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Customer::create([
            'name' => 'Consumidor Final',
            'address' => 'N/A',
            'email' => 'final@cliente.com',
            'phone' => '00000000',
            'type' => 'Consumidor Final',
            'customer_commission_1_percentage' => 0,
            'customer_commission_1_threshold' => 0
        ]);

        Customer::create([
            'name' => 'Cliente de Prueba 1',
            'address' => 'Calle 123',
            'email' => 'cliente1@test.com',
            'phone' => '555-0001',
            'type' => 'Mayoristas',
            'customer_commission_1_percentage' => 5.00,
            'customer_commission_1_threshold' => 30
        ]);

        Customer::create([
            'name' => 'Cliente de Prueba 2',
            'address' => 'Carrera 45',
            'email' => 'cliente2@test.com',
            'phone' => '555-0002',
            'type' => 'Otro',
            'customer_commission_1_percentage' => 0,
            'customer_commission_1_threshold' => 0
        ]);
    }
}
