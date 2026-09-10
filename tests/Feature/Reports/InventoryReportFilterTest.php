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
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'reports.sales']);
        $this->user->givePermissionTo('reports.sales');

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

    public function test_inventory_report_can_search_by_multiple_tags()
    {
        // rawMaterial has tag soplados (from setUp)
        // Let's attach tag pet to the finished product
        $tagB = \App\Models\Tag::firstOrCreate(['name' => 'pet']);
        $this->product->tags()->attach($tagB->id);

        Livewire::actingAs($this->user)
            ->test(InventoryReport::class)
            ->set('product_type', 'all')
            ->set('search', 'soplados pet')
            ->assertSee('RAW INSUMO MATERIAL') // Tag soplados
            ->assertSee('FINISHED PRODUCT'); // Tag pet
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

    public function test_inventory_report_calculates_and_toggles_unit_cost_and_price_columns()
    {
        $category = Category::first();
        $supplier = Supplier::first();

        // Create container product with units in name
        $pPet = Product::create([
            'name' => 'ENVASE PET 330ML 200UND',
            'sku' => 'PET-330',
            'price' => 28.00,
            'cost' => 12.54,
            'status' => 'available',
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'is_raw_material' => false,
            'type' => 'physical',
            'stock_qty' => 10,
            'low_stock' => 5,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(InventoryReport::class);

        // Check columns present in configuration
        $columns = $component->get('columns');
        $this->assertArrayHasKey('cost_unit', $columns);
        $this->assertArrayHasKey('price_unit', $columns);
        $this->assertTrue($columns['cost_unit']);
        $this->assertTrue($columns['price_unit']);

        // Check products data calculations
        $productsData = $component->instance()->getProductsData();
        $itemPet = collect($productsData->items())->firstWhere('id', $pPet->id);
        $this->assertNotNull($itemPet);
        $this->assertEquals(200, $itemPet->units_per_package);
        $this->assertEquals(0.0627, $itemPet->unit_cost);
        $this->assertEquals(0.1400, $itemPet->unit_price);

        // Check standard product has null unit values
        $itemStandard = collect($productsData->items())->firstWhere('id', $this->product->id);
        $this->assertNotNull($itemStandard);
        $this->assertNull($itemStandard->units_per_package);
        $this->assertNull($itemStandard->unit_cost);
        $this->assertNull($itemStandard->unit_price);

        // Toggle column off
        $component->set('columns.cost_unit', false)
            ->assertSet('columns.cost_unit', false);
    }

    public function test_inventory_report_pdf_endpoint_renders_with_unit_columns()
    {
        $this->actingAs($this->user);

        $params = [
            'columns' => json_encode([
                'sku' => true,
                'name' => true,
                'cost' => true,
                'cost_unit' => true,
                'price' => true,
                'price_unit' => true,
            ]),
        ];

        $response = $this->get(route('reports.inventory.pdf', $params));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
