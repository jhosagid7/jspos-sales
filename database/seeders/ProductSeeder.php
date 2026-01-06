<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $category = Category::first();
        $supplier = Supplier::first();

        $products = [
            [
                'sku' => 'P001',
                'name' => 'Laptop Pro 15',
                'description' => 'Laptop de alto rendimiento',
                'cost' => 800.00,
                'price' => 1200.00,
                'stock_qty' => 10,
            ],
            [
                'sku' => 'P002',
                'name' => 'Smartphone X',
                'description' => 'Teléfono inteligente de última generación',
                'cost' => 400.00,
                'price' => 650.00,
                'stock_qty' => 25,
            ],
            [
                'sku' => 'P003',
                'name' => 'Monitor 4K 27"',
                'description' => 'Monitor ultra HD',
                'cost' => 200.00,
                'price' => 350.00,
                'stock_qty' => 15,
            ],
            [
                'sku' => 'P004',
                'name' => 'Teclado Mecánico',
                'description' => 'Teclado RGB para gaming',
                'cost' => 45.00,
                'price' => 85.00,
                'stock_qty' => 50,
            ],
            [
                'sku' => 'P005',
                'name' => 'Mouse Inalámbrico',
                'description' => 'Mouse ergonómico',
                'cost' => 15.00,
                'price' => 30.00,
                'stock_qty' => 100,
            ],
        ];

        foreach ($products as $p) {
            Product::create([
                'sku' => $p['sku'],
                'name' => $p['name'],
                'description' => $p['description'],
                'type' => 'physical',
                'status' => 'available',
                'cost' => $p['cost'],
                'price' => $p['price'],
                'manage_stock' => 1,
                'stock_qty' => $p['stock_qty'],
                'low_stock' => 5,
                'supplier_id' => $supplier->id,
                'category_id' => $category->id,
            ]);
        }
    }
}
