<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ModelNameNormalizationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_name_is_automatically_normalized_to_title_case()
    {
        $user = User::factory()->create([
            'name' => 'maria guillen',
        ]);

        $this->assertEquals('Maria Guillen', $user->name);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Maria Guillen',
        ]);
    }

    /** @test */
    public function customer_name_is_automatically_normalized_to_title_case()
    {
        $customer = Customer::create([
            'name' => '  inversiones   los   llanos   c.a.  ',
            'phone' => '04141234567',
        ]);

        $this->assertEquals('Inversiones Los Llanos C.A.', $customer->name);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Inversiones Los Llanos C.A.',
        ]);
    }

    /** @test */
    public function supplier_name_is_automatically_normalized_to_title_case()
    {
        $supplier = Supplier::create([
            'name' => 'JUAN DE LA ROSA',
        ]);

        $this->assertEquals('Juan de la Rosa', $supplier->name);
        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Juan de la Rosa',
        ]);
    }

    /** @test */
    public function warehouse_name_is_always_uppercase_and_partner_is_title_case()
    {
        $warehouse = Warehouse::create([
            'name' => 'bodega central de distribución',
            'partner_name' => 'pedro del valle',
        ]);

        $this->assertEquals('BODEGA CENTRAL DE DISTRIBUCIÓN', $warehouse->name);
        $this->assertEquals('Pedro del Valle', $warehouse->partner_name);
        $this->assertDatabaseHas('warehouses', [
            'id' => $warehouse->id,
            'name' => 'BODEGA CENTRAL DE DISTRIBUCIÓN',
            'partner_name' => 'Pedro del Valle',
        ]);
    }

    /** @test */
    public function product_name_is_always_uppercase()
    {
        $category = Category::create(['name' => 'General']);
        $supplier = Supplier::create(['name' => 'Empresas Polar']);
        $product = Product::create([
            'name' => 'harina pan 1kg maíz blanco',
            'sku' => 'HPAN-001',
            'cost' => 1.00,
            'price' => 1.50,
            'stock_qty' => 0,
            'low_stock' => 0,
            'supplier_id' => $supplier->id,
            'category_id' => $category->id,
            'status' => 'available',
        ]);

        $this->assertEquals('HARINA PAN 1KG MAÍZ BLANCO', $product->name);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'HARINA PAN 1KG MAÍZ BLANCO',
        ]);
    }
}
