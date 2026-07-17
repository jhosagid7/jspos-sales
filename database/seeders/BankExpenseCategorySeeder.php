<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BankExpenseCategory;

class BankExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Nómina / Sueldos',
                'icon' => 'fa-users',
                'color' => '#4e73df',
                'is_essential' => true,
                'sort' => 1,
            ],
            [
                'name' => 'Alquiler / Arrendamiento',
                'icon' => 'fa-building',
                'color' => '#1cc88a',
                'is_essential' => true,
                'sort' => 2,
            ],
            [
                'name' => 'Servicios Públicos (Luz, Agua, Internet)',
                'icon' => 'fa-bolt',
                'color' => '#36b9cc',
                'is_essential' => true,
                'sort' => 3,
            ],
            [
                'name' => 'Proveedores de Mercancía',
                'icon' => 'fa-truck',
                'color' => '#f6c23e',
                'is_essential' => true,
                'sort' => 4,
            ],
            [
                'name' => 'Impuestos / Tasas',
                'icon' => 'fa-file-invoice-dollar',
                'color' => '#e74a3b',
                'is_essential' => true,
                'sort' => 5,
            ],
            [
                'name' => 'Transporte / Fletes',
                'icon' => 'fa-shipping-fast',
                'color' => '#858796',
                'is_essential' => true,
                'sort' => 6,
            ],
            [
                'name' => 'Mantenimiento / Reparaciones',
                'icon' => 'fa-wrench',
                'color' => '#5a5c69',
                'is_essential' => false,
                'sort' => 7,
            ],
            [
                'name' => 'Publicidad / Mercadeo',
                'icon' => 'fa-ad',
                'color' => '#fd7e14',
                'is_essential' => false,
                'sort' => 8,
            ],
            [
                'name' => 'Suministros de Oficina',
                'icon' => 'fa-paperclip',
                'color' => '#6f42c1',
                'is_essential' => false,
                'sort' => 9,
            ],
            [
                'name' => 'Gastos Varios / Otros',
                'icon' => 'fa-shopping-cart',
                'color' => '#e83e8c',
                'is_essential' => false,
                'sort' => 10,
            ],
        ];

        foreach ($categories as $category) {
            BankExpenseCategory::updateOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}
