<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Configuration;
use App\Models\Warehouse;
use App\Models\ProductWarehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PublicProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_products_json_via_browser_accessible_api_route()
    {
        $warehouse = Warehouse::create([
            'id' => 1,
            'name' => 'Almacén Central',
            'is_active' => 1
        ]);

        Configuration::create([
            'business_name' => 'JSPOS Test',
            'default_warehouse_id' => $warehouse->id,
            'check_stock_reservation' => false,
        ]);

        $category = Category::create([
            'name' => 'General'
        ]);

        $supplier = Supplier::create([
            'name' => 'Distribuidor Principal'
        ]);

        $product = Product::create([
            'name' => 'Bolsa Plástica 20x30',
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'cost' => 1.00,
            'price' => 2.50,
            'barcode' => '1234567890',
            'stock_qty' => 50,
            'low_stock' => 5,
            'manage_stock' => 1,
            'show_in_sales' => true,
        ]);

        ProductWarehouse::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'stock_qty' => 50,
        ]);

        $response = $this->getJson('/api/products-json');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $product->id,
            'name' => 'BOLSA PLÁSTICA 20X30',
            'price' => 2.5,
        ]);
    }
}
