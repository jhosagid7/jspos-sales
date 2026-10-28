<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\PriceList;
use App\Models\Configuration;
use App\Livewire\Products;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductPriceListTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $category;
    protected $supplier;
    protected $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->warehouse = Warehouse::create([
            'name' => 'Almacén Principal',
            'is_active' => true,
        ]);

        Configuration::create([
            'business_name' => 'Test Business',
            'default_warehouse_id' => $this->warehouse->id,
        ]);

        $this->category = Category::create(['name' => 'General']);
        $this->supplier = Supplier::create([
            'name' => 'Proveedor General',
            'address' => 'Local',
            'phone' => '123456',
            'email' => 'p@example.com'
        ]);
    }

    public function test_can_add_prices_with_different_decimal_counts()
    {
        $product = Product::create([
            'name' => 'BANDEJAS A1 EUROPLAST',
            'sku' => 'BAN-001',
            'cost' => 3.00,
            'price' => 5.00,
            'stock_qty' => 10,
            'low_stock' => 2,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(Products::class)
            ->call('Edit', $product->id);

        // 1. Test adding price with 2 decimals (e.g. 5.23)
        $component->set('form.value', '5.23')
            ->call('storeTempPrice')
            ->assertDispatched('noty', msg: 'Precio agregado correctamente');

        $this->assertCount(1, $component->get('form.values'));
        $this->assertEquals('5.23', $component->get('form.values')[0]['price']);

        // 2. Test adding price with 1 decimal (e.g. 6.5)
        $component->set('form.value', '6.5')
            ->call('storeTempPrice')
            ->assertDispatched('noty', msg: 'Precio agregado correctamente');

        $this->assertCount(2, $component->get('form.values'));

        // 3. Test adding price with comma decimal (e.g. 7,45)
        $component->set('form.value', '7,45')
            ->call('storeTempPrice')
            ->assertDispatched('noty', msg: 'Precio agregado correctamente');

        $this->assertCount(3, $component->get('form.values'));
        $this->assertEquals('7.45', $component->get('form.values')[2]['price']);

        // 4. Test adding price with integer (e.g. 8)
        $component->set('form.value', '8')
            ->call('storeTempPrice')
            ->assertDispatched('noty', msg: 'Precio agregado correctamente');

        $this->assertCount(4, $component->get('form.values'));

        // 5. Test adding price with 4 decimals (e.g. 9.1234)
        $component->set('form.value', '9.1234')
            ->call('storeTempPrice')
            ->assertDispatched('noty', msg: 'Precio agregado correctamente');

        $this->assertCount(5, $component->get('form.values'));
    }

    public function test_rejects_prices_with_more_than_four_decimals_or_invalid_values()
    {
        $product = Product::create([
            'name' => 'BANDEJAS TEST',
            'sku' => 'BAN-002',
            'cost' => 3.00,
            'price' => 5.00,
            'stock_qty' => 10,
            'low_stock' => 2,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(Products::class)
            ->call('Edit', $product->id);

        // 5 decimals should fail
        $component->set('form.value', '5.12345')
            ->call('storeTempPrice')
            ->assertDispatched('noty', msg: '¡El valor debe ser un número positivo con un máximo de cuatro decimales!');

        $this->assertCount(0, $component->get('form.values'));

        // Negative should fail
        $component->set('form.value', '-5.20')
            ->call('storeTempPrice')
            ->assertDispatched('noty', msg: '¡El valor debe ser un número positivo con un máximo de cuatro decimales!');

        $this->assertCount(0, $component->get('form.values'));

        // Zero should fail
        $component->set('form.value', '0')
            ->call('storeTempPrice')
            ->assertDispatched('noty', msg: '¡El valor debe ser un número positivo con un máximo de cuatro decimales!');

        $this->assertCount(0, $component->get('form.values'));
    }

    public function test_prevents_duplicate_prices_numerically()
    {
        $product = Product::create([
            'name' => 'BANDEJAS DUP TEST',
            'sku' => 'BAN-003',
            'cost' => 3.00,
            'price' => 5.00,
            'stock_qty' => 10,
            'low_stock' => 2,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(Products::class)
            ->call('Edit', $product->id);

        // Add 5.20
        $component->set('form.value', '5.20')
            ->call('storeTempPrice')
            ->assertDispatched('noty', msg: 'Precio agregado correctamente');

        // Try adding 5.2 (numerically identical)
        $component->set('form.value', '5.2')
            ->call('storeTempPrice')
            ->assertDispatched('noty', msg: '¡El precio ya existe!');

        $this->assertCount(1, $component->get('form.values'));
    }

    public function test_can_remove_price_by_uuid_and_renders_quoted_id_in_blade()
    {
        $product = Product::create([
            'name' => 'BANDEJAS REMOVE TEST',
            'sku' => 'BAN-004',
            'cost' => 3.00,
            'price' => 5.00,
            'stock_qty' => 10,
            'low_stock' => 2,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(Products::class)
            ->call('Edit', $product->id);

        // Add 5.25 (generates a UUID id)
        $component->set('form.value', '5.25')
            ->call('storeTempPrice');

        $values = $component->get('form.values');
        $this->assertCount(1, $values);
        $addedId = $values[0]['id'];

        // Verify HTML renders with quotes around UUID
        $component->assertSeeHtml("removeTempPrice('{$addedId}')");

        // Remove the price
        $component->call('removeTempPrice', $addedId)
            ->assertDispatched('noty', msg: 'Precio eliminado correctamente');

        $this->assertCount(0, $component->get('form.values'));
    }
}
