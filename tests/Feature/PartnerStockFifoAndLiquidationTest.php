<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Warehouse;
use App\Models\Transfer;
use App\Models\TransferDetail;
use App\Models\TransferStockLayer;
use App\Models\SaleLayerConsumption;
use App\Models\Configuration;
use App\Services\PartnerStockService;
use App\Services\ConfigurationService;
use App\Livewire\Reports\PartnerSalesLiquidationReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Carbon\Carbon;

class PartnerStockFifoAndLiquidationTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $partnerWarehouse1;
    protected $partnerWarehouse2;
    protected $storeWarehouse;
    protected $product;
    protected $customer;
    protected $partnerService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\CurrencySeeder::class);

        // Reset ConfigurationService cache
        $ref = new \ReflectionClass(ConfigurationService::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null);

        // Create Configuration with partner module active
        Configuration::create([
            'business_name' => 'Comercializadora Socios Demo',
            'taxpayer_id' => 'J-12345678-9',
            'address' => 'Av. Principal',
            'city' => 'Bogota / Caracas',
            'phone' => '12345678',
            'decimals' => 2,
            'vat' => 0,
            'printer_name' => 'POS',
            'credit_days' => 15,
            'module_partner_sales' => true,
            'local_overrides' => ['module_partner_sales' => true],
        ]);

        \Spatie\Permission\Models\Permission::findOrCreate('reports.partner_sales');
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($role);
        $this->adminUser->givePermissionTo('reports.partner_sales');

        $this->partnerWarehouse1 = Warehouse::create([
            'name' => 'Depósito Socio 1',
            'is_active' => true,
            'is_partner_warehouse' => true,
            'partner_name' => 'Carlos (Socio 1)'
        ]);
        $this->partnerWarehouse2 = Warehouse::create([
            'name' => 'Depósito Socio 2',
            'is_active' => true,
            'is_partner_warehouse' => true,
            'partner_name' => 'Ana (Socio 2)'
        ]);
        $this->storeWarehouse = Warehouse::create([
            'name' => 'Depósito Tienda / Ventas',
            'is_active' => true,
            'is_partner_warehouse' => false
        ]);

        $category = \App\Models\Category::create(['name' => 'General']);
        $supplier = \App\Models\Supplier::create(['name' => 'Proveedor Principal', 'phone' => '12345678']);
        $this->product = Product::create([
            'name' => 'Producto X1 Compartido',
            'sku' => '75010001',
            'cost' => 10.00,
            'price' => 20.00,
            'stock_qty' => 100,
            'low_stock' => 5,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
        ]);

        $this->customer = Customer::create([
            'name' => 'Cliente Mostrador Prueba',
            'taxpayer_id' => 'V-99999999',
            'phone' => '04140000000',
        ]);

        $this->partnerService = app(PartnerStockService::class);
    }

    /**
     * Test 1: Register transfer layers from Partner 1 and Partner 2 to Store.
     */
    public function test_transfers_create_fifo_stock_layers()
    {
        // Transfer 1: 10 units from Partner 1 to Store
        $transfer1 = Transfer::create([
            'from_warehouse_id' => $this->partnerWarehouse1->id,
            'to_warehouse_id' => $this->storeWarehouse->id,
            'user_id' => $this->adminUser->id,
            'status' => 'completed',
        ]);
        $detail1 = TransferDetail::create([
            'transfer_id' => $transfer1->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'received_quantity' => 10,
        ]);
        $this->partnerService->registerTransferLayers($transfer1);

        // Transfer 2: 10 units from Partner 2 to Store
        $transfer2 = Transfer::create([
            'from_warehouse_id' => $this->partnerWarehouse2->id,
            'to_warehouse_id' => $this->storeWarehouse->id,
            'user_id' => $this->adminUser->id,
            'status' => 'completed',
        ]);
        $detail2 = TransferDetail::create([
            'transfer_id' => $transfer2->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'received_quantity' => 10,
        ]);
        $this->partnerService->registerTransferLayers($transfer2);

        $layers = TransferStockLayer::where('product_id', $this->product->id)->orderBy('id')->get();
        $this->assertCount(2, $layers);
        $this->assertEquals(10, $layers[0]->remaining_quantity);
        $this->assertEquals($this->partnerWarehouse1->id, $layers[0]->origin_warehouse_id);
        $this->assertEquals(10, $layers[1]->remaining_quantity);
        $this->assertEquals($this->partnerWarehouse2->id, $layers[1]->origin_warehouse_id);
    }

    /**
     * Test 2: POS sale of 15 units consumes 10 from Partner 1 and 5 from Partner 2 strictly via FIFO.
     */
    public function test_pos_sale_consumes_layers_in_strict_fifo_order()
    {
        // Setup layers: 10 from Partner 1, 10 from Partner 2
        $transfer1 = Transfer::create([
            'from_warehouse_id' => $this->partnerWarehouse1->id,
            'to_warehouse_id' => $this->storeWarehouse->id,
            'user_id' => $this->adminUser->id,
            'status' => 'completed',
        ]);
        TransferDetail::create([
            'transfer_id' => $transfer1->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'received_quantity' => 10,
        ]);
        $this->partnerService->registerTransferLayers($transfer1);

        $transfer2 = Transfer::create([
            'from_warehouse_id' => $this->partnerWarehouse2->id,
            'to_warehouse_id' => $this->storeWarehouse->id,
            'user_id' => $this->adminUser->id,
            'status' => 'completed',
        ]);
        TransferDetail::create([
            'transfer_id' => $transfer2->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'received_quantity' => 10,
        ]);
        $this->partnerService->registerTransferLayers($transfer2);

        // Create Sale of 15 units in the Store Warehouse
        $sale = Sale::create([
            'total' => 300.00,
            'total_usd' => 300.00,
            'items' => 15,
            'status' => 'paid',
            'type' => 1,
            'customer_id' => $this->customer->id,
            'user_id' => $this->adminUser->id,
            'warehouse_id' => $this->storeWarehouse->id,
            'invoice_number' => 'FAC-001',
        ]);
        $detail = SaleDetail::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->storeWarehouse->id,
            'quantity' => 15,
            'regular_price' => 20.00,
            'sale_price' => 20.00,
            'discount' => 0,
        ]);

        // Consume layers
        $this->partnerService->consumeLayersForSale($sale);

        // Verify layers state
        $layer1 = TransferStockLayer::where('origin_warehouse_id', $this->partnerWarehouse1->id)->first();
        $layer2 = TransferStockLayer::where('origin_warehouse_id', $this->partnerWarehouse2->id)->first();

        // Layer 1 should be completely exhausted (0 remaining)
        $this->assertEquals(0, $layer1->remaining_quantity);
        // Layer 2 should have 5 remaining (10 - 5 = 5)
        $this->assertEquals(5, $layer2->remaining_quantity);

        // Verify consumptions table
        $consumptions = SaleLayerConsumption::where('sale_id', $sale->id)->orderBy('id')->get();
        $this->assertCount(2, $consumptions);

        // First slice: 10 units attributed to Partner 1
        $this->assertEquals(10, $consumptions[0]->quantity);
        $this->assertEquals($this->partnerWarehouse1->id, $consumptions[0]->origin_warehouse_id);
        $this->assertEquals(200.00, $consumptions[0]->total_price);
        $this->assertEquals(10.00, (float) $consumptions[0]->unit_cost);
        $this->assertEquals(100.00, (float) $consumptions[0]->total_cost);
        $this->assertEquals(100.00, (float) $consumptions[0]->profit);
        $this->assertEquals(50.0, (float) $consumptions[0]->margin_percentage);

        // Second slice: 5 units attributed to Partner 2
        $this->assertEquals(5, $consumptions[1]->quantity);
        $this->assertEquals($this->partnerWarehouse2->id, $consumptions[1]->origin_warehouse_id);
        $this->assertEquals(100.00, $consumptions[1]->total_price);
        $this->assertEquals(10.00, (float) $consumptions[1]->unit_cost);
        $this->assertEquals(50.00, (float) $consumptions[1]->total_cost);
        $this->assertEquals(50.00, (float) $consumptions[1]->profit);
        $this->assertEquals(50.0, (float) $consumptions[1]->margin_percentage);
    }

    /**
     * Test 3: Voiding a sale restores the layers back to their original quantities.
     */
    public function test_voiding_sale_restores_fifo_stock_layers()
    {
        $transfer = Transfer::create([
            'from_warehouse_id' => $this->partnerWarehouse1->id,
            'to_warehouse_id' => $this->storeWarehouse->id,
            'user_id' => $this->adminUser->id,
            'status' => 'completed',
        ]);
        TransferDetail::create([
            'transfer_id' => $transfer->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'received_quantity' => 10,
        ]);
        $this->partnerService->registerTransferLayers($transfer);

        $sale = Sale::create([
            'total' => 160.00,
            'total_usd' => 160.00,
            'items' => 8,
            'status' => 'paid',
            'type' => 1,
            'customer_id' => $this->customer->id,
            'user_id' => $this->adminUser->id,
            'warehouse_id' => $this->storeWarehouse->id,
            'invoice_number' => 'FAC-002',
        ]);
        SaleDetail::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->storeWarehouse->id,
            'quantity' => 8,
            'regular_price' => 20.00,
            'sale_price' => 20.00,
            'discount' => 0,
        ]);

        $this->partnerService->consumeLayersForSale($sale);

        $layer = TransferStockLayer::where('origin_warehouse_id', $this->partnerWarehouse1->id)->first();
        $this->assertEquals(2, $layer->remaining_quantity);

        // Now restore / void sale
        $this->partnerService->restoreLayersForSale($sale);

        $layer->refresh();
        $this->assertEquals(10, $layer->remaining_quantity);
        $this->assertEquals(0, SaleLayerConsumption::where('sale_id', $sale->id)->count());
    }

    /**
     * Test 4: Livewire Report correctly attributes sales and liquidation values per partner.
     */
    public function test_liquidation_report_livewire_component_and_calculations()
    {
        $this->actingAs($this->adminUser);

        // Register transfers and sales
        $transfer1 = Transfer::create([
            'from_warehouse_id' => $this->partnerWarehouse1->id,
            'to_warehouse_id' => $this->storeWarehouse->id,
            'user_id' => $this->adminUser->id,
            'status' => 'completed',
        ]);
        TransferDetail::create([
            'transfer_id' => $transfer1->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'received_quantity' => 10,
        ]);
        $this->partnerService->registerTransferLayers($transfer1);

        $transfer2 = Transfer::create([
            'from_warehouse_id' => $this->partnerWarehouse2->id,
            'to_warehouse_id' => $this->storeWarehouse->id,
            'user_id' => $this->adminUser->id,
            'status' => 'completed',
        ]);
        TransferDetail::create([
            'transfer_id' => $transfer2->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'received_quantity' => 10,
        ]);
        $this->partnerService->registerTransferLayers($transfer2);

        $sale = Sale::create([
            'total' => 300.00,
            'total_usd' => 300.00,
            'items' => 15,
            'status' => 'paid',
            'type' => 1,
            'customer_id' => $this->customer->id,
            'user_id' => $this->adminUser->id,
            'warehouse_id' => $this->storeWarehouse->id,
            'invoice_number' => 'FAC-003',
        ]);
        SaleDetail::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->storeWarehouse->id,
            'quantity' => 15,
            'regular_price' => 20.00,
            'sale_price' => 20.00,
            'discount' => 0,
        ]);
        $this->partnerService->consumeLayersForSale($sale);

        // Test Livewire component renders and shows correct KPI amounts
        Livewire::test(PartnerSalesLiquidationReport::class)
            ->set('dateFrom', Carbon::now()->subDay()->format('Y-m-d'))
            ->set('dateTo', Carbon::now()->addDay()->format('Y-m-d'))
            ->assertSee('$300.00') // Total amount sold
            ->assertSee('15.00')   // Total units sold
            ->assertSee('Depósito Socio 1')
            ->assertSee('Depósito Socio 2');

        // Test filtering by Partner 1 specifically
        Livewire::test(PartnerSalesLiquidationReport::class)
            ->set('dateFrom', Carbon::now()->subDay()->format('Y-m-d'))
            ->set('dateTo', Carbon::now()->addDay()->format('Y-m-d'))
            ->set('origin_warehouse_id', $this->partnerWarehouse1->id)
            ->assertSee('$200.00') // Only Partner 1's share
            ->assertSee('10.00');
    }

    /**
     * Test 5: Verify zero N+1 queries during report generation.
     */
    public function test_liquidation_report_has_zero_n_plus_one_queries()
    {
        $this->actingAs($this->adminUser);

        // Populate multiple transfers and sales
        for ($i = 0; $i < 3; $i++) {
            $t = Transfer::create([
                'from_warehouse_id' => $this->partnerWarehouse1->id,
                'to_warehouse_id' => $this->storeWarehouse->id,
                'user_id' => $this->adminUser->id,
                'status' => 'completed',
            ]);
            TransferDetail::create([
                'transfer_id' => $t->id,
                'product_id' => $this->product->id,
                'quantity' => 5,
                'received_quantity' => 5,
            ]);
            $this->partnerService->registerTransferLayers($t);
        }

        $sale = Sale::create([
            'total' => 200.00,
            'total_usd' => 200.00,
            'items' => 10,
            'status' => 'paid',
            'type' => 1,
            'customer_id' => $this->customer->id,
            'user_id' => $this->adminUser->id,
            'warehouse_id' => $this->storeWarehouse->id,
            'invoice_number' => 'FAC-BENCHMARK',
        ]);
        SaleDetail::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->storeWarehouse->id,
            'quantity' => 10,
            'regular_price' => 20.00,
            'sale_price' => 20.00,
            'discount' => 0,
        ]);
        $this->partnerService->consumeLayersForSale($sale);

        // Count queries for detailed report
        DB::enableQueryLog();
        $component = new PartnerSalesLiquidationReport();
        $component->dateFrom = Carbon::now()->subDay()->format('Y-m-d');
        $component->dateTo = Carbon::now()->addDay()->format('Y-m-d');
        $component->viewMode = 'detailed';
        $component->render();

        $queryCount = count(DB::getQueryLog());
        // Should execute a bounded number of queries (approx 5-6 queries total, never proportional to items)
        $this->assertLessThan(15, $queryCount, "Detected potential N+1 query leak, executed queries: {$queryCount}");
    }

    /**
     * Test 6: Transfers from standard/internal warehouses (NOT marked as partner) do NOT create partner FIFO layers.
     */
    public function test_transfers_from_non_partner_warehouse_do_not_create_layers()
    {
        $centralWarehouse = Warehouse::create([
            'name' => 'Depósito Central Galpón',
            'is_active' => true,
            'is_partner_warehouse' => false,
        ]);

        $transfer = Transfer::create([
            'from_warehouse_id' => $centralWarehouse->id,
            'to_warehouse_id' => $this->storeWarehouse->id,
            'user_id' => $this->adminUser->id,
            'status' => 'completed',
        ]);
        TransferDetail::create([
            'transfer_id' => $transfer->id,
            'product_id' => $this->product->id,
            'quantity' => 20,
            'received_quantity' => 20,
        ]);

        $this->partnerService->registerTransferLayers($transfer);

        // Verify NO layers created for this transfer
        $layerCount = TransferStockLayer::where('transfer_id', $transfer->id)->count();
        $this->assertEquals(0, $layerCount);
    }

    /**
     * Test 7: Liquidation report shows physical origin stock in partner warehouses and consignment stock layers.
     */
    public function test_liquidation_report_shows_origin_physical_stock_and_views()
    {
        $this->actingAs($this->adminUser);

        // Put 50 units in partnerWarehouse1 and 30 units in partnerWarehouse2
        \App\Models\ProductWarehouse::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->partnerWarehouse1->id,
            'stock_qty' => 50,
        ]);
        \App\Models\ProductWarehouse::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->partnerWarehouse2->id,
            'stock_qty' => 30,
        ]);

        // Transfer 10 units from partnerWarehouse1 to store
        $transfer = Transfer::create([
            'from_warehouse_id' => $this->partnerWarehouse1->id,
            'to_warehouse_id' => $this->storeWarehouse->id,
            'user_id' => $this->adminUser->id,
            'status' => 'completed',
        ]);
        TransferDetail::create([
            'transfer_id' => $transfer->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'received_quantity' => 10,
        ]);
        $this->partnerService->registerTransferLayers($transfer);

        // Test Livewire component shows origin physical stock KPI (80.00 total)
        Livewire::test(PartnerSalesLiquidationReport::class)
            ->assertSee('80.00') // 50 + 30
            ->assertSee('Stock en Depósito Socio')
            ->assertSee('Stock Remanente en Tienda')
            // Switch to origin_stock view mode
            ->set('viewMode', 'origin_stock')
            ->assertSee('Depósito Socio 1')
            ->assertSee('Depósito Socio 2')
            ->assertSee('75010001')
            ->assertSee('50.00')
            ->assertSee('30.00')
            // Switch to consignment_stock view mode
            ->set('viewMode', 'consignment_stock')
            ->assertSee('Depósito Socio 1')
            ->assertSee('Depósito Tienda / Ventas')
            ->assertSee('10.00') // Initial layer transferred
            ->assertSee('$10.00') // Unit cost base
            ->assertSee('$100.00'); // Remaining value ($10 * 10)

        // Filter by partnerWarehouse1 specifically
        Livewire::test(PartnerSalesLiquidationReport::class)
            ->set('origin_warehouse_id', $this->partnerWarehouse1->id)
            ->set('viewMode', 'origin_stock')
            ->assertSee('50.00')
            ->assertDontSee('30.00');
    }

    /**
     * Test 8: PDF export works for origin_stock and consignment_stock view modes.
     */
    public function test_partner_sales_pdf_export_supports_all_view_modes()
    {
        $this->actingAs($this->adminUser);

        \App\Models\ProductWarehouse::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->partnerWarehouse1->id,
            'stock_qty' => 25,
        ]);

        $responseOrigin = $this->get(route('reports.partner.sales.pdf', ['viewMode' => 'origin_stock']));
        $responseOrigin->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $responseOrigin->headers->get('Content-Type'));

        $responseConsignment = $this->get(route('reports.partner.sales.pdf', ['viewMode' => 'consignment_stock']));
        $responseConsignment->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $responseConsignment->headers->get('Content-Type'));
    }

    /**
     * Test 9: Liquidation report calculates and displays costs, profits, and margin percentages in web and PDF.
     */
    public function test_liquidation_report_calculates_and_displays_costs_profits_and_margins()
    {
        $this->actingAs($this->adminUser);

        // Setup a transfer and sale
        $transfer = Transfer::create([
            'from_warehouse_id' => $this->partnerWarehouse1->id,
            'to_warehouse_id' => $this->storeWarehouse->id,
            'user_id' => $this->adminUser->id,
            'status' => 'completed',
        ]);
        TransferDetail::create([
            'transfer_id' => $transfer->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'received_quantity' => 10,
        ]);
        $this->partnerService->registerTransferLayers($transfer);

        // Sale of 10 units at $20 (total $200), cost is $10 (total cost $100), profit is $100 (margin 50%)
        $sale = Sale::create([
            'total' => 200.00,
            'total_usd' => 200.00,
            'items' => 10,
            'status' => 'paid',
            'type' => 1,
            'customer_id' => $this->customer->id,
            'user_id' => $this->adminUser->id,
            'warehouse_id' => $this->storeWarehouse->id,
            'invoice_number' => 'FAC-COST-01',
        ]);
        $detail = SaleDetail::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->storeWarehouse->id,
            'quantity' => 10,
            'regular_price' => 20.00,
            'sale_price' => 20.00,
            'discount' => 0,
        ]);
        $this->partnerService->consumeLayersForSale($sale);

        // Test Livewire component calculates KPIs and renders columns
        $component = Livewire::test(PartnerSalesLiquidationReport::class)
            ->assertSee('Costo Total Mercancía')
            ->assertSee('Ganancia Bruta / Utilidad')
            ->assertSee('Margen de Ganancia Promedio')
            ->assertSee('100.00') // Cost and profit
            ->assertSee('50.00%') // Margin percentage
            // Check detailed view
            ->set('viewMode', 'detailed')
            ->assertSee('FAC-COST-01')
            ->assertSee('100.00');

        // Test PDF export with summary view
        $pdfSummary = $this->get(route('reports.partner.sales.pdf', ['viewMode' => 'summary']));
        $pdfSummary->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $pdfSummary->headers->get('Content-Type'));

        // Test PDF export with detailed view
        $pdfDetailed = $this->get(route('reports.partner.sales.pdf', ['viewMode' => 'detailed']));
        $pdfDetailed->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $pdfDetailed->headers->get('Content-Type'));
    }
}