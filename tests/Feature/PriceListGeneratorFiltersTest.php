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
}
