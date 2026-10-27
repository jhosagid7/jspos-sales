<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Configuration;
use Livewire\Livewire;
use App\Livewire\Purchases;
use App\Livewire\QuickCreateProduct;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PurchaseProductCreationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $category;
    protected $supplier;
    protected $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $ref = new \ReflectionClass(\App\Services\ConfigurationService::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        Configuration::create([
            'business_name' => 'Plásticos M&F Steel Test',
            'vat' => 16,
            'plan_type' => 'pro',
            'local_overrides' => ['module_purchases' => true, 'module_advanced_products' => true],
        ]);

        $this->warehouse = Warehouse::create([
            'name' => 'Tienda Principal',
            'status' => 'active',
        ]);

        $this->category = Category::create(['name' => 'Vasos y Desechables']);
        $this->supplier = Supplier::create([
            'name' => 'Proveedor Plásticos C.A.',
            'phone' => '04141234567',
            'address' => 'Zona Industrial'
        ]);

        Permission::firstOrCreate(['name' => 'products.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchases.create', 'guard_name' => 'web']);

        $this->user = User::factory()->create([
            'name' => 'Operador Compras',
            'email' => 'compras@test.com'
        ]);
        $this->user->givePermissionTo(['products.create', 'purchases.create']);
    }

    public function test_quick_create_product_component_creates_product_with_all_options()
    {
        $this->actingAs($this->user);

        $productData = [
            'name' => 'VASO TERMICO 16OZ PREMIUM',
            'sku' => 'VAS-TERM-16',
            'cost' => 1.25,
            'price' => 2.50,
            'type' => 'physical',
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'brand' => 'ThermoPack',
            'presentation' => 'Bulto x 500 Und',
            'description' => 'Vaso térmico para bebidas calientes y frías',
            'stock_qty' => 50,
            'low_stock' => 10,
            'max_stock' => 500,
            'manage_stock' => 1,
            'allow_decimal' => false,
            'is_variable_quantity' => false,
            'show_in_sales' => true,
            'is_raw_material' => false,
        ];

        Livewire::test(QuickCreateProduct::class)
            ->set('form.name', $productData['name'])
            ->set('form.sku', $productData['sku'])
            ->set('form.cost', $productData['cost'])
            ->set('form.price', $productData['price'])
            ->set('form.type', $productData['type'])
            ->set('form.category_id', $productData['category_id'])
            ->set('form.supplier_id', $productData['supplier_id'])
            ->set('form.brand', $productData['brand'])
            ->set('form.presentation', $productData['presentation'])
            ->set('form.description', $productData['description'])
            ->set('form.stock_qty', $productData['stock_qty'])
            ->set('form.low_stock', $productData['low_stock'])
            ->set('form.max_stock', $productData['max_stock'])
            ->set('form.manage_stock', $productData['manage_stock'])
            ->set('form.allow_decimal', $productData['allow_decimal'])
            ->set('form.is_variable_quantity', $productData['is_variable_quantity'])
            ->set('form.show_in_sales', $productData['show_in_sales'])
            ->set('form.is_raw_material', $productData['is_raw_material'])
            ->call('store')
            ->assertDispatched('product-created')
            ->assertDispatched('close-modal-create-product');

        $this->assertDatabaseHas('products', [
            'name' => 'VASO TERMICO 16OZ PREMIUM',
            'sku' => 'VAS-TERM-16',
            'cost' => 1.25,
            'price' => 2.50,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'brand' => 'ThermoPack',
            'presentation' => 'Bulto x 500 Und',
        ]);
    }

    public function test_purchases_component_auto_adds_product_when_product_created_event_dispatched()
    {
        $this->actingAs($this->user);

        $product = Product::create([
            'name' => 'PLATO TÉRMICO #2',
            'sku' => 'PLATO-02',
            'cost' => 0.85,
            'price' => 1.70,
            'stock_qty' => 10,
            'low_stock' => 5,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'type' => 'physical',
            'status' => 'available',
        ]);

        Livewire::test(Purchases::class)
            ->dispatch('product-created', product: $product->id)
            ->assertSee('PLATO TÉRMICO #2')
            ->assertSet('itemsCart', 1);
    }

    public function test_data_products_autocomplete_finds_newly_created_product()
    {
        $this->actingAs($this->user);

        $product = Product::create([
            'name' => 'BANDEJA DE ANIME CT-3',
            'sku' => 'ANIME-CT3-BARCODE',
            'cost' => 2.10,
            'price' => 4.20,
            'stock_qty' => 20,
            'low_stock' => 5,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'type' => 'physical',
            'status' => 'available',
        ]);

        $response = $this->getJson(route('data.products', ['q' => 'CT-3']));
        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'BANDEJA DE ANIME CT-3']);

        $responseSku = $this->getJson(route('data.products', ['q' => 'ANIME-CT3']));
        $responseSku->assertStatus(200);
        $responseSku->assertJsonFragment(['sku' => 'ANIME-CT3-BARCODE']);
    }

    public function test_native_product_search_component_searches_and_dispatches_selection()
    {
        $this->actingAs($this->user);

        $product = Product::create([
            'name' => 'CUCHARAS PLASTICAS TRANSPARENTES',
            'sku' => 'CUCH-TR-100',
            'cost' => 1.45,
            'price' => 2.90,
            'stock_qty' => 50,
            'low_stock' => 10,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'type' => 'physical',
            'status' => 'available',
        ]);

        Livewire::test(\App\Livewire\ProductSearch::class)
            ->set('search', 'CUCHARAS')
            ->assertCount('products', 1)
            ->assertSee('CUCHARAS PLASTICAS TRANSPARENTES')
            ->call('selectProduct', $product->id)
            ->assertDispatched('purchase_product', product: $product->id)
            ->assertSet('search', '')
            ->assertSet('products', []);
    }
}
