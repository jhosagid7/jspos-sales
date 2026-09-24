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
    public function user_name_is_automatically_normalized_to_uppercase()
    {
        $user = User::factory()->create([
            'name' => 'maria guillen',
        ]);

        $this->assertEquals('MARIA GUILLEN', $user->name);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'MARIA GUILLEN',
        ]);
    }

    /** @test */
    public function customer_name_is_automatically_normalized_to_uppercase()
    {
        $customer = Customer::create([
            'name' => '  inversiones   los   llanos   c.a.  ',
            'phone' => '04141234567',
        ]);

        $this->assertEquals('INVERSIONES LOS LLANOS C.A.', $customer->name);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'INVERSIONES LOS LLANOS C.A.',
        ]);
    }

    /** @test */
    public function supplier_name_is_automatically_normalized_to_uppercase()
    {
        $supplier = Supplier::create([
            'name' => 'juan de la rosa',
        ]);

        $this->assertEquals('JUAN DE LA ROSA', $supplier->name);
        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'JUAN DE LA ROSA',
        ]);
    }

    /** @test */
    public function warehouse_name_and_partner_are_both_always_uppercase()
    {
        $warehouse = Warehouse::create([
            'name' => 'bodega central de distribución',
            'partner_name' => 'pedro del valle',
        ]);

        $this->assertEquals('BODEGA CENTRAL DE DISTRIBUCIÓN', $warehouse->name);
        $this->assertEquals('PEDRO DEL VALLE', $warehouse->partner_name);
        $this->assertDatabaseHas('warehouses', [
            'id' => $warehouse->id,
            'name' => 'BODEGA CENTRAL DE DISTRIBUCIÓN',
            'partner_name' => 'PEDRO DEL VALLE',
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
