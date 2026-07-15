<?php

namespace Tests\Feature\Reports;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Configuration;
use Livewire\Livewire;
use App\Livewire\Reports\InventoryReport;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InventoryReportFilterTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $product;
    protected $rawMaterial;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $warehouse = \App\Models\Warehouse::create([
            'id' => 1,
            'name' => 'TIENDA PRINCIPAL',
            'is_active' => 1,
        ]);

        Configuration::create([
            'business_name' => 'EMPRESA TEST',
            'default_warehouse_id' => $warehouse->id,
            'bcv_rate' => 60.00,
            'binance_rate' => 70.00,
            'binance_markup_points' => 0.00,
        ]);

        $category = Category::create(['name' => 'TEST CATEGORY']);
        $supplier = Supplier::create(['name' => 'TEST SUPPLIER']);

        // Create standard finished product
        $this->product = Product::create([
            'name' => 'FINISHED PRODUCT',
            'sku' => 'PROD-123',
            'price' => 10.00,
            'cost' => 5.00,
            'status' => 'available',
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'is_raw_material' => false,
            'type' => 'physical',
            'stock_qty' => 10,
            'low_stock' => 5,
        ]);

        // Create raw material/supply
        $this->rawMaterial = Product::create([
            'name' => 'RAW INSUMO MATERIAL',
            'sku' => 'RAW-456',
            'price' => 2.00,
            'cost' => 1.00,
            'status' => 'available',
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'is_raw_material' => true,
            'type' => 'physical',
            'stock_qty' => 20,
            'low_stock' => 5,
        ]);

        $tag = \App\Models\Tag::firstOrCreate(['name' => 'soplados']);
        $this->rawMaterial->tags()->attach($tag->id);
    }

    public function test_inventory_report_loads_with_default_products_filter()
    {
        Livewire::actingAs($this->user)
            ->test(InventoryReport::class)
            ->assertSet('product_type', 'products')
            ->assertSee('FINISHED PRODUCT')
            ->assertDontSee('RAW INSUMO MATERIAL');
    }

    public function test_inventory_report_can_filter_by_raw_materials_only()
    {
        Livewire::actingAs($this->user)
            ->test(InventoryReport::class)
            ->set('product_type', 'raw_materials')
            ->assertSee('RAW INSUMO MATERIAL')
            ->assertDontSee('FINISHED PRODUCT');
    }

    public function test_inventory_report_can_show_all_products_and_raw_materials()
    {
        Livewire::actingAs($this->user)
            ->test(InventoryReport::class)
            ->set('product_type', 'all')
            ->assertSee('FINISHED PRODUCT')
            ->assertSee('RAW INSUMO MATERIAL');
    }

    public function test_inventory_report_can_search_by_tag()
    {
        Livewire::actingAs($this->user)
            ->test(InventoryReport::class)
            ->set('product_type', 'all')
            ->set('search', 'soplados')
            ->assertSee('RAW INSUMO MATERIAL')
            ->assertDontSee('FINISHED PRODUCT');
    }

    public function test_open_pdf_preview_includes_product_type_parameter()
    {
        $component = Livewire::actingAs($this->user)
            ->test(InventoryReport::class)
            ->set('product_type', 'raw_materials')
            ->call('openPdfPreview')
            ->assertSet('showPdfModal', true);

        $pdfUrl = $component->get('pdfUrl');
        $this->assertStringContainsString('product_type=raw_materials', $pdfUrl);
    }
}
