<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Configuration;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Customer;
use App\Livewire\Reports\RotationReport;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class RotationReportTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $unauthorizedUser;
    protected $customer;
    protected $category;
    protected $supplier;
    protected $p1, $p2, $p3, $p4;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.installed' => false,
            'tenant.modules' => ['module_advanced_reports'],
        ]);

        // Create Configuration
        Configuration::create([
            'business_name' => 'Rotation Report Test Business',
            'taxpayer_id' => 'J-12345678-9',
            'address' => 'Test Address',
            'phone' => '0212-0000000',
            'email' => 'business@test.com',
            'decimals' => 2,
            'purchasing_coverage_days' => 30,
        ]);

        $this->seed(\Database\Seeders\CurrencySeeder::class);

        // Create users
        $this->adminUser = User::factory()->create(['name' => 'Report Admin']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'reports.stock']);
        $this->adminUser->givePermissionTo('reports.stock');

        $this->unauthorizedUser = User::factory()->create(['name' => 'Standard User']);

        // Create Customer
        $this->customer = Customer::create([
            'name' => 'Rotation Customer',
            'type' => 'Consumidor Final',
            'taxpayer_id' => 'V12345678',
            'address' => 'Caracas',
            'phone' => '0412-0000000',
        ]);

        // Create Category and Supplier
        $this->category = Category::create(['name' => 'Electronics']);
        $this->supplier = Supplier::create([
            'name' => 'Global Supplier',
            'taxpayer_id' => 'J99999999',
            'address' => 'Supplier Address',
            'phone' => '0212-1111111',
        ]);

        // Create Products
        // P1: cost = 40, price = 75, stock = 10
        $this->p1 = Product::create([
            'name' => 'Product Alpha',
            'sku' => 'P-ALPHA-01',
            'cost' => 40.00,
            'price' => 75.00,
            'price_usd' => 75.00,
            'show_in_sales' => true,
            'stock_qty' => 10,
            'manage_stock' => true,
            'low_stock' => 0,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
        ]);

        // P2: cost = 8, price = 15, stock = 20
        $this->p2 = Product::create([
            'name' => 'Product Beta',
            'sku' => 'P-BETA-02',
            'cost' => 8.00,
            'price' => 15.00,
            'price_usd' => 15.00,
            'show_in_sales' => true,
            'stock_qty' => 20,
            'manage_stock' => true,
            'low_stock' => 0,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
        ]);

        // P3: cost = 5, price = 10, stock = 30
        $this->p3 = Product::create([
            'name' => 'Product Gamma',
            'sku' => 'P-GAMMA-03',
            'cost' => 5.00,
            'price' => 10.00,
            'price_usd' => 10.00,
            'show_in_sales' => true,
            'stock_qty' => 30,
            'manage_stock' => true,
            'low_stock' => 0,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
        ]);

        // P4: cost = 20, price = 50, stock = 5 (No sales)
        $this->p4 = Product::create([
            'name' => 'Product Delta',
            'sku' => 'P-DELTA-04',
            'cost' => 20.00,
            'price' => 50.00,
            'price_usd' => 50.00,
            'show_in_sales' => true,
            'stock_qty' => 5,
            'manage_stock' => true,
            'low_stock' => 0,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
        ]);

        // Create Sales in the period
        $sale = Sale::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->adminUser->id,
            'total' => 100.00,
            'total_usd' => 100.00,
            'items' => 3,
            'status' => 'paid',
            'type' => 'cash',
            'created_at' => Carbon::now()->subDays(5),
        ]);

        // Detail 1: P1 sold 1 unit @ 75.00 (Total = 75.00)
        SaleDetail::create([
            'sale_id' => $sale->id,
            'product_id' => $this->p1->id,
            'quantity' => 1,
            'regular_price' => 75.00,
            'sale_price' => 75.00,
            'discount' => 0.00,
            'freight_amount' => 0.00,
        ]);

        // Detail 2: P2 sold 1 unit @ 15.00 (Total = 15.00)
        SaleDetail::create([
            'sale_id' => $sale->id,
            'product_id' => $this->p2->id,
            'quantity' => 1,
            'regular_price' => 15.00,
            'sale_price' => 15.00,
            'discount' => 0.00,
            'freight_amount' => 0.00,
        ]);

        // Detail 3: P3 sold 1 unit @ 10.00 (Total = 10.00)
        SaleDetail::create([
            'sale_id' => $sale->id,
            'product_id' => $this->p3->id,
            'quantity' => 1,
            'regular_price' => 10.00,
            'sale_price' => 10.00,
            'discount' => 0.00,
            'freight_amount' => 0.00,
        ]);
    }

    public function test_rotation_report_component_renders_for_authorized_user()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('reports.rotation'));
        $response->assertStatus(200);
        $response->assertSeeLivewire(RotationReport::class);
    }

    public function test_rotation_report_component_denies_unauthorized_user()
    {
        $this->actingAs($this->unauthorizedUser);

        $response = $this->get(route('reports.rotation'));
        $response->assertStatus(403);
    }

    public function test_rotation_report_calculates_correct_kpis_and_margins()
    {
        Livewire::actingAs($this->adminUser)
            ->test(RotationReport::class)
            ->assertSet('totalCapital', 810.00) // 10*40 + 20*8 + 30*5 + 5*20 = 400+160+150+100 = 810
            ->assertSet('idleCapital', 100.00)  // P4 only: 5*20 = 100
            ->assertSet('totalMargin', 47.00)   // (75-40) + (15-8) + (10-5) = 35 + 7 + 5 = 47
            ->assertSet('avgMarginPercent', 47.00); // 47 margin / 100 sales = 47%
    }

    public function test_rotation_report_assigns_correct_abc_pareto_classes()
    {
        $component = Livewire::actingAs($this->adminUser)
            ->test(RotationReport::class);

        $abcMap = $component->get('abcMap');

        // P1 Sales = 75 USD (75% of 100.00 total) -> <= 80% -> Class A
        $this->assertEquals('A', $abcMap[$this->p1->id]);

        // P2 Sales = 15 USD (cumulative = 90%) -> 80% < 90% <= 95% -> Class B
        $this->assertEquals('B', $abcMap[$this->p2->id]);

        // P3 Sales = 10 USD (cumulative = 100%) -> > 95% -> Class C
        $this->assertEquals('C', $abcMap[$this->p3->id]);

        // P4 Sales = 0 USD -> Class C
        $this->assertEquals('C', $abcMap[$this->p4->id]);
    }

    public function test_rotation_report_pdf_generation_endpoint()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(RotationReport::class)
            ->call('generatePdf')
            ->assertFileDownloaded('Reporte_Rotacion.pdf');
    }

    public function test_rotation_report_filters_by_tag()
    {
        $tag = \App\Models\Tag::create(['name' => 'Premium']);

        // Attach tag to P1 (Product Alpha)
        $this->p1->tags()->attach($tag->id);

        Livewire::actingAs($this->adminUser)
            ->test(RotationReport::class)
            ->set('tagId', $tag->id)
            ->assertSet('totalCapital', 400.00) // P1: cost 40 * stock 10 = 400
            ->assertSet('idleCapital', 0.00)    // P1 was sold, so no idle capital among filtered
            ->assertSet('totalMargin', 35.00)   // P1 margin: 75 - 40 = 35
            ->assertSet('avgMarginPercent', 46.67); // P1 margin percent: 35 / 75 = 46.67%
    }

    public function test_rotation_report_toggles_interpretation_modal_and_generates_analysis()
    {
        Livewire::actingAs($this->adminUser)
            ->test(RotationReport::class)
            ->assertSet('showInterpretationModal', false)
            ->call('toggleInterpretationModal')
            ->assertSet('showInterpretationModal', true)
            ->call('toggleInterpretationModal')
            ->assertSet('showInterpretationModal', false);

        // Check text generation without customer
        $component = Livewire::actingAs($this->adminUser)->test(RotationReport::class);
        $htmlGeneral = $component->instance()->getInterpretation();
        $this->assertStringContainsString('Análisis de Salud de Inventario Global', $htmlGeneral);

        // Check text generation with customer
        $componentWithCustomer = Livewire::actingAs($this->adminUser)
            ->test(RotationReport::class)
            ->set('customerId', $this->customer->id);
        $htmlCustomer = $componentWithCustomer->instance()->getInterpretation();
        $this->assertStringContainsString('Análisis de Compras de Cliente', $htmlCustomer);
    }

    public function test_rotation_report_catalog_pdf_generation_endpoint()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(RotationReport::class)
            ->set('customerId', $this->customer->id)
            ->call('generateCatalogPdf')
            ->assertFileDownloaded('Catalogo_Ofertas_' . str_replace(' ', '_', $this->customer->name) . '.pdf');
    }

    public function test_rotation_report_calculates_correct_totals_when_sale_is_in_foreign_currency()
    {
        // Clear existing sales to isolate this test
        SaleDetail::query()->delete();
        Sale::query()->delete();

        // Product cost = 5.00, price = 10.00
        $product = Product::create([
            'name' => 'Foreign Product',
            'sku' => 'P-FOREIGN-99',
            'cost' => 5.00,
            'price' => 10.00,
            'price_usd' => 10.00,
            'show_in_sales' => true,
            'stock_qty' => 10,
            'manage_stock' => true,
            'low_stock' => 0,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
        ]);

        // Create Sale with primary_exchange_rate = 2.0
        $sale = Sale::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->adminUser->id,
            'total' => 20.00, // in foreign currency
            'total_usd' => 10.00, // in USD
            'primary_exchange_rate' => 2.0,
            'items' => 1,
            'status' => 'paid',
            'type' => 'cash',
            'created_at' => Carbon::now()->subDays(5),
        ]);

        // SaleDetail: quantity = 1, price = 20.00 in foreign currency
        SaleDetail::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'regular_price' => 20.00,
            'sale_price' => 20.00,
            'discount' => 0.00,
            'freight_amount' => 0.00,
        ]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(RotationReport::class);

        // Fetch processed products data
        $data = $component->instance()->getRotationData();
        $processedProduct = collect($data->items())->firstWhere('id', $product->id);

        $this->assertNotNull($processedProduct);
        // Sales USD should be 10.00 (20.00 / 2.0 exchange rate)
        $this->assertEquals(10.00, $processedProduct->sales_usd);
        // Margin USD should be 5.00 (10.00 sales_usd - 5.00 cost)
        $this->assertEquals(5.00, $processedProduct->margin_usd);
    }

    public function test_rotation_report_handles_selected_products_filtering_robustly()
    {
        $this->actingAs($this->adminUser);

        // Scenario 1: selectedProducts is a flat list
        Livewire::test(RotationReport::class)
            ->set('selectedProducts', [$this->p1->id, $this->p2->id])
            ->call('generatePdf')
            ->assertFileDownloaded('Reporte_Rotacion.pdf');

        // Scenario 2: selectedProducts is an associative array (e.g. from checkbox clicks)
        Livewire::test(RotationReport::class)
            ->set('selectedProducts', [
                $this->p1->id => true,
                $this->p2->id => true,
                $this->p3->id => false,
            ])
            ->call('generatePdf')
            ->assertFileDownloaded('Reporte_Rotacion.pdf');
    }

    public function test_rotation_report_handles_selected_kpis_rendering_and_pdf()
    {
        $this->actingAs($this->adminUser);

        // Scenario 1: Some KPIs selected
        Livewire::test(RotationReport::class)
            ->set('selectedKpis', ['totalCapital', 'totalMargin'])
            ->call('generatePdf')
            ->assertFileDownloaded('Reporte_Rotacion.pdf');

        // Scenario 2: No KPIs selected (all unchecked)
        Livewire::test(RotationReport::class)
            ->set('selectedKpis', [])
            ->call('generatePdf')
            ->assertFileDownloaded('Reporte_Rotacion.pdf');
    }

    public function test_rotation_report_can_sort_selected_products_and_render_pdf_in_that_order()
    {
        $this->actingAs($this->adminUser);

        // Select products in order: p2, p1, p3
        $selected = [$this->p2->id, $this->p1->id, $this->p3->id];

        $component = Livewire::test(RotationReport::class)
            ->set('selectedProducts', $selected);

        // Move p1 (index 1) UP to index 0 -> Order becomes: p1, p2, p3
        $component->call('moveProductUp', $this->p1->id);
        $this->assertEquals([$this->p1->id, $this->p2->id, $this->p3->id], $component->instance()->getSelectedIds());

        // Move p3 (index 2) UP to index 1 -> Order becomes: p1, p3, p2
        $component->call('moveProductUp', $this->p3->id);
        $this->assertEquals([$this->p1->id, $this->p3->id, $this->p2->id], $component->instance()->getSelectedIds());

        // Call generatePdf to ensure it generates fine
        $component->call('generatePdf')
            ->assertFileDownloaded('Reporte_Rotacion.pdf');
    }

    public function test_rotation_report_can_reorder_via_drag_and_drop()
    {
        $this->actingAs($this->adminUser);

        // Select products in order: p2, p1, p3
        $selected = [$this->p2->id, $this->p1->id, $this->p3->id];

        $component = Livewire::test(RotationReport::class)
            ->set('selectedProducts', $selected);

        // Move index 0 (p2) to index 1 -> Order becomes: p1, p2, p3
        $component->call('reorderProducts', 0, 1);
        $this->assertEquals([$this->p1->id, $this->p2->id, $this->p3->id], $component->instance()->getSelectedIds());

        // Move index 2 (p3) to index 0 -> Order becomes: p3, p1, p2
        $component->call('reorderProducts', 2, 0);
        $this->assertEquals([$this->p3->id, $this->p1->id, $this->p2->id], $component->instance()->getSelectedIds());
    }
}
