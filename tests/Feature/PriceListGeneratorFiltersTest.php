<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Tag;
use App\Models\Configuration;
use App\Services\ConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class PriceListGeneratorFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $supplierA;
    protected $supplierB;
    protected $tagA;
    protected $tagB;
    protected $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed currencies
        $this->seed(\Database\Seeders\CurrencySeeder::class);

        // Reset ConfigurationService static cache
        $ref = new \ReflectionClass(ConfigurationService::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null);

        // Create Configuration
        Configuration::create([
            'business_name' => 'Test Business',
            'taxpayer_id' => '12345678',
            'address' => 'Test Address 123',
            'city' => 'Caracas',
            'phone' => '0212-5555555',
            'decimals' => 2,
            'vat' => 16,
            'printer_name' => 'EPSON',
            'credit_days' => 15,
            'sequential_cut_off_date' => '2026-06-03 00:00:00'
        ]);

        $this->adminUser = User::factory()->create();

        // Create Suppliers
        $this->supplierA = Supplier::create([
            'name' => 'Supplier A',
            'taxpayer_id' => 'J1',
            'address' => 'Addr 1',
            'phone' => '123',
        ]);
        $this->supplierB = Supplier::create([
            'name' => 'Supplier B',
            'taxpayer_id' => 'J2',
            'address' => 'Addr 2',
            'phone' => '456',
        ]);

        // Create Tags
        $this->tagA = Tag::create(['name' => 'Tag A']);
        $this->tagB = Tag::create(['name' => 'Tag B']);

        // Create Category
        $this->category = Category::create(['name' => 'Cat']);
    }

    public function test_price_list_generator_loads_suppliers_and_tags()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(\App\Livewire\PriceListGenerator::class)
            ->assertSee('Supplier A')
            ->assertSee('Supplier B')
            ->assertSee('Tag A')
            ->assertSee('Tag B');
    }

    public function test_price_list_generator_filters_by_supplier()
    {
        $this->actingAs($this->adminUser);

        // Create products
        $productA = Product::create([
            'sku' => 'SKU-A',
            'name' => 'Product A',
            'cost' => 10,
            'price' => 15,
            'status' => 'available',
            'show_in_sales' => true,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplierA->id,
            'manage_stock' => false,
            'stock_qty' => 100,
            'low_stock' => 0,
        ]);

        $productB = Product::create([
            'sku' => 'SKU-B',
            'name' => 'Product B',
            'cost' => 20,
            'price' => 25,
            'status' => 'available',
            'show_in_sales' => true,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplierB->id,
            'manage_stock' => false,
            'stock_qty' => 100,
            'low_stock' => 0,
        ]);

        // When selectedSupplierId is set, generate should download PDF and only contain Product A
        $response = Livewire::test(\App\Livewire\PriceListGenerator::class)
            ->set('selectedSupplierId', $this->supplierA->id)
            ->call('generate');

        $response->assertFileDownloaded('Lista_Precios_' . now()->format('d-m-Y') . '.pdf');
    }

    public function test_price_list_generator_filters_by_tag()
    {
        $this->actingAs($this->adminUser);

        // Create products
        $productA = Product::create([
            'sku' => 'SKU-A',
            'name' => 'Product A',
            'cost' => 10,
            'price' => 15,
            'status' => 'available',
            'show_in_sales' => true,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplierA->id,
            'manage_stock' => false,
            'stock_qty' => 100,
            'low_stock' => 0,
        ]);
        $productA->tags()->attach($this->tagA->id);

        $productB = Product::create([
            'sku' => 'SKU-B',
            'name' => 'Product B',
            'cost' => 20,
            'price' => 25,
            'status' => 'available',
            'show_in_sales' => true,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplierB->id,
            'manage_stock' => false,
            'stock_qty' => 100,
            'low_stock' => 0,
        ]);
        $productB->tags()->attach($this->tagB->id);

        // When selectedTagId is set, generate should download PDF
        $response = Livewire::test(\App\Livewire\PriceListGenerator::class)
            ->set('selectedTagId', $this->tagA->id)
            ->call('generate');

        $response->assertFileDownloaded('Lista_Precios_' . now()->format('d-m-Y') . '.pdf');
    }

    public function test_price_list_generator_filters_by_category()
    {
        $this->actingAs($this->adminUser);

        $categoryB = Category::create(['name' => 'Cat B']);

        Product::create([
            'sku' => 'SKU-A',
            'name' => 'Product A',
            'cost' => 10,
            'price' => 15,
            'status' => 'available',
            'show_in_sales' => true,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplierA->id,
            'manage_stock' => false,
            'stock_qty' => 100,
            'low_stock' => 0,
        ]);

        Product::create([
            'sku' => 'SKU-B',
            'name' => 'Product B',
            'cost' => 20,
            'price' => 25,
            'status' => 'available',
            'show_in_sales' => true,
            'category_id' => $categoryB->id,
            'supplier_id' => $this->supplierB->id,
            'manage_stock' => false,
            'stock_qty' => 100,
            'low_stock' => 0,
        ]);

        // When selectedCategoryId is set, generate should download PDF
        $response = Livewire::test(\App\Livewire\PriceListGenerator::class)
            ->set('selectedCategoryId', $this->category->id)
            ->call('generate');

        $response->assertFileDownloaded('Lista_Precios_' . now()->format('d-m-Y') . '.pdf');
    }

    public function test_price_list_generator_only_bought_products()
    {
        $this->actingAs($this->adminUser);

        $customer = \App\Models\Customer::create([
            'name' => 'John Doe',
            'taxpayer_id' => 'V12345678',
            'address' => 'Test Address',
            'city' => 'Caracas',
            'phone' => '123456',
            'email' => 'john@doe.com',
            'type' => 'Mayoristas',
        ]);

        $productA = Product::create([
            'sku' => 'SKU-A',
            'name' => 'Product A',
            'cost' => 10,
            'price' => 15,
            'status' => 'available',
            'show_in_sales' => true,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplierA->id,
            'manage_stock' => false,
            'stock_qty' => 100,
            'low_stock' => 0,
        ]);

        Product::create([
            'sku' => 'SKU-B',
            'name' => 'Product B',
            'cost' => 20,
            'price' => 25,
            'status' => 'available',
            'show_in_sales' => true,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplierB->id,
            'manage_stock' => false,
            'stock_qty' => 100,
            'low_stock' => 0,
        ]);

        // Create a sale for Product A only
        $sale = \App\Models\Sale::create([
            'total' => 15,
            'total_usd' => 15,
            'items' => 1,
            'invoice_number' => 'F00000001',
            'status' => 'paid',
            'type' => 'cash',
            'customer_id' => $customer->id,
            'user_id' => $this->adminUser->id,
        ]);
        \App\Models\SaleDetail::create([
            'sale_id' => $sale->id,
            'product_id' => $productA->id,
            'quantity' => 1,
            'regular_price' => 15,
            'sale_price' => 15,
            'discount' => 0,
        ]);

        // Mock PDF loadView to inspect data passed
        $pdfMock = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdfMock->shouldReceive('output')->andReturn('pdf content');

        \Barryvdh\DomPDF\Facade\Pdf::shouldReceive('loadView')
            ->once()
            ->with('pdf.price-list', \Mockery::on(function($data) {
                $catGroup = $data['groupedData']['Cat'] ?? [];
                $skus = array_column($catGroup, 'sku');
                return in_array('SKU-A', $skus) && !in_array('SKU-B', $skus);
            }))
            ->andReturn($pdfMock);

        $response = Livewire::test(\App\Livewire\PriceListGenerator::class)
            ->set('customerId', $customer->id)
            ->set('onlyBoughtProducts', true)
            ->call('generate');

        $response->assertStatus(200);
    }

    public function test_price_list_generator_without_commissions()
    {
        $this->actingAs($this->adminUser);

        Product::create([
            'sku' => 'SKU-A',
            'name' => 'Product A',
            'cost' => 10,
            'price' => 100,
            'status' => 'available',
            'show_in_sales' => true,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplierA->id,
            'manage_stock' => false,
            'stock_qty' => 100,
            'low_stock' => 0,
        ]);

        // Mock PDF loadView to check that final_price is base price (100) * (1 + 0.16) = 116 (if VAT is 16)
        $pdfMock = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdfMock->shouldReceive('output')->andReturn('pdf content');

        \Barryvdh\DomPDF\Facade\Pdf::shouldReceive('loadView')
            ->once()
            ->with('pdf.price-list', \Mockery::on(function($data) {
                $catGroup = $data['groupedData']['Cat'] ?? [];
                foreach ($catGroup as $item) {
                    if ($item['sku'] === 'SKU-A') {
                        return abs($item['final_price'] - 116.0) < 0.001;
                    }
                }
                return false;
            }))
            ->andReturn($pdfMock);

        $response = Livewire::test(\App\Livewire\PriceListGenerator::class)
            ->set('applyCommissionsToggle', false)
            ->call('generate');

        $response->assertStatus(200);
    }

    public function test_price_list_generator_grouped_by_supplier()
    {
        $this->actingAs($this->adminUser);

        Product::create([
            'sku' => 'SKU-A',
            'name' => 'Product A',
            'cost' => 10,
            'price' => 15,
            'status' => 'available',
            'show_in_sales' => true,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplierA->id,
            'manage_stock' => false,
            'stock_qty' => 100,
            'low_stock' => 0,
        ]);

        $pdfMock = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdfMock->shouldReceive('output')->andReturn('pdf content');

        \Barryvdh\DomPDF\Facade\Pdf::shouldReceive('loadView')
            ->once()
            ->with('pdf.price-list', \Mockery::on(function($data) {
                // Should group by supplier name ("Supplier A")
                $supplierGroup = $data['groupedData']['Supplier A'] ?? [];
                $skus = array_column($supplierGroup, 'sku');
                return in_array('SKU-A', $skus);
            }))
            ->andReturn($pdfMock);

        $response = Livewire::test(\App\Livewire\PriceListGenerator::class)
            ->set('groupBy', 'supplier')
            ->call('generate');

        $response->assertStatus(200);
    }

    public function test_price_list_generator_grouped_by_tag()
    {
        $this->actingAs($this->adminUser);

        $product = Product::create([
            'sku' => 'SKU-A',
            'name' => 'Product A',
            'cost' => 10,
            'price' => 15,
            'status' => 'available',
            'show_in_sales' => true,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplierA->id,
            'manage_stock' => false,
            'stock_qty' => 100,
            'low_stock' => 0,
        ]);
        $product->tags()->attach($this->tagA->id);

        $pdfMock = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdfMock->shouldReceive('output')->andReturn('pdf content');

        \Barryvdh\DomPDF\Facade\Pdf::shouldReceive('loadView')
            ->once()
            ->with('pdf.price-list', \Mockery::on(function($data) {
                // Should group by tag name ("Tag A")
                $tagGroup = $data['groupedData']['Tag A'] ?? [];
                $skus = array_column($tagGroup, 'sku');
                return in_array('SKU-A', $skus);
            }))
            ->andReturn($pdfMock);

        $response = Livewire::test(\App\Livewire\PriceListGenerator::class)
            ->set('groupBy', 'tag')
            ->call('generate');

        $response->assertStatus(200);
    }

    public function test_price_list_generator_not_grouped()
    {
        $this->actingAs($this->adminUser);

        Product::create([
            'sku' => 'SKU-A',
            'name' => 'Product A',
            'cost' => 10,
            'price' => 15,
            'status' => 'available',
            'show_in_sales' => true,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplierA->id,
            'manage_stock' => false,
            'stock_qty' => 100,
            'low_stock' => 0,
        ]);

        $pdfMock = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdfMock->shouldReceive('output')->andReturn('pdf content');

        \Barryvdh\DomPDF\Facade\Pdf::shouldReceive('loadView')
            ->once()
            ->with('pdf.price-list', \Mockery::on(function($data) {
                // Should group everything under the empty string ""
                $emptyGroup = $data['groupedData'][''] ?? [];
                $skus = array_column($emptyGroup, 'sku');
                return in_array('SKU-A', $skus) && count($data['groupedData']) === 1;
            }))
            ->andReturn($pdfMock);

        $response = Livewire::test(\App\Livewire\PriceListGenerator::class)
            ->set('groupBy', 'none')
            ->call('generate');

        $response->assertStatus(200);
    }

    public function test_price_list_generator_with_configurable_decimals()
    {
        $this->actingAs($this->adminUser);

        // Mock PDF loadView to check that decimals is passed correctly
        $pdfMock = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdfMock->shouldReceive('output')->andReturn('pdf content');

        \Barryvdh\DomPDF\Facade\Pdf::shouldReceive('loadView')
            ->once()
            ->with('pdf.price-list', \Mockery::on(function($data) {
                return isset($data['decimals']) && $data['decimals'] === 3;
            }))
            ->andReturn($pdfMock);

        $response = Livewire::test(\App\Livewire\PriceListGenerator::class)
            ->set('decimals', 3)
            ->call('generate');

        $response->assertStatus(200);
    }

    public function test_price_list_generator_saves_decimals_config()
    {
        $this->actingAs($this->adminUser);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'sales.configure_price_list']);
        $this->adminUser->givePermissionTo('sales.configure_price_list');

        Livewire::test(\App\Livewire\PriceListGenerator::class)
            ->set('decimals', 4)
            ->call('saveConfig');

        $config = Configuration::first();
        $this->assertEquals(4, $config->price_list_decimals);
    }
}

