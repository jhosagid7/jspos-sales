<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Configuration;
use App\Models\Shift;
use App\Models\ProductionLog;
use App\Models\ProductionOutput;
use App\Models\ProductionMaterial;
use App\Livewire\Reports\ProductMovementsReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Carbon\Carbon;
use Spatie\Permission\Models\Permission;

class ProductKardexAndCutTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $mainWarehouse;
    protected $plantWarehouse;
    protected $category;
    protected $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.installed' => true]);

        $this->mock(\App\Services\LicenseService::class, function ($mock) {
            $mock->shouldReceive('checkLicense')->andReturn([
                'status' => 'active',
                'days_remaining' => 30,
                'modules' => [],
                'max_devices' => 10,
            ]);
            $mock->shouldReceive('getClientId')->andReturn('test-client-id');
        });

        $this->mainWarehouse = Warehouse::create([
            'name' => 'Tienda Principal',
            'address' => 'Local Comercial',
            'is_active' => true,
        ]);

        $this->plantWarehouse = Warehouse::create([
            'name' => 'Planta Soplados',
            'address' => 'Zona Industrial',
            'is_active' => true,
        ]);

        Configuration::create([
            'default_warehouse_id' => $this->mainWarehouse->id,
            'soplados_warehouse_id' => $this->plantWarehouse->id,
            'production_materials_warehouse_id' => $this->plantWarehouse->id,
            'business_name' => 'JSPOS Test',
        ]);

        Permission::findOrCreate('reports.stock', 'web');

        $this->user = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'warehouse_id' => $this->mainWarehouse->id,
        ]);
        $this->user->givePermissionTo('reports.stock');

        $this->actingAs($this->user);

        $this->category = Category::create(['name' => 'Envases']);
        $this->supplier = Supplier::create([
            'name' => 'Proveedor Principal',
            'taxpayer_id' => 'J-00000000-0',
            'address' => 'Calle 1',
            'phone' => '1234567',
        ]);
    }

    public function test_inventory_cut_sets_exact_physical_stock_and_creates_adjustments()
    {
        // 1. Create product with messy historical state:
        // Main Warehouse = -50 (legacy sales without initial stock)
        // Plant Warehouse = 20
        // Global = -30
        $product = Product::create([
            'name' => 'Galón Blanco 1GL',
            'sku' => 'GAL-001',
            'cost' => 0.50,
            'price' => 1.00,
            'stock_qty' => -30,
            'manage_stock' => true,
            'low_stock' => 0,
            'status' => 'available',
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->mainWarehouse->id,
        ]);

        ProductWarehouse::create([
            'product_id' => $product->id,
            'warehouse_id' => $this->mainWarehouse->id,
            'stock_qty' => -50,
        ]);

        ProductWarehouse::create([
            'product_id' => $product->id,
            'warehouse_id' => $this->plantWarehouse->id,
            'stock_qty' => 20,
        ]);

        // 2. Perform Inventory Cut via Livewire
        // Physical count: Main Warehouse = 150, Plant Warehouse = 80 -> Global should be 230
        $component = Livewire::test(ProductMovementsReport::class)
            ->set('product_id', $product->id)
            ->call('openCutModal')
            ->assertDispatched('show-cut-modal');

        $cutWarehouses = [
            $this->mainWarehouse->id => [
                'name' => $this->mainWarehouse->name,
                'current_stock' => -50,
                'counted_stock' => 150, // +200 diff -> Cargo
            ],
            $this->plantWarehouse->id => [
                'name' => $this->plantWarehouse->name,
                'current_stock' => 20,
                'counted_stock' => 80, // +60 diff -> Cargo
            ],
        ];

        $component->set('cut_warehouses', $cutWarehouses)
            ->set('cut_notes', 'Corte de Inventario Inicial Marzo')
            ->call('saveInventoryCut')
            ->assertDispatched('hide-cut-modal');

        // 3. Verify Database State
        $pwMain = ProductWarehouse::where('product_id', $product->id)->where('warehouse_id', $this->mainWarehouse->id)->first();
        $this->assertEquals(150.0, floatval($pwMain->stock_qty));

        $pwPlant = ProductWarehouse::where('product_id', $product->id)->where('warehouse_id', $this->plantWarehouse->id)->first();
        $this->assertEquals(80.0, floatval($pwPlant->stock_qty));

        $product->refresh();
        $this->assertEquals(230.0, floatval($product->stock_qty));

        // Verify Cargo records
        $this->assertDatabaseHas('cargos', [
            'warehouse_id' => $this->mainWarehouse->id,
            'motive' => 'Corte de Inventario: Corte de Inventario Inicial Marzo',
        ]);
        $this->assertDatabaseHas('cargo_details', [
            'product_id' => $product->id,
            'quantity' => 200.0,
        ]);

        $this->assertDatabaseHas('cargos', [
            'warehouse_id' => $this->plantWarehouse->id,
            'motive' => 'Corte de Inventario: Corte de Inventario Inicial Marzo',
        ]);
        $this->assertDatabaseHas('cargo_details', [
            'product_id' => $product->id,
            'quantity' => 60.0,
        ]);
    }

    public function test_kardex_includes_factory_production_outputs_and_materials()
    {
        $product = Product::create([
            'name' => 'Galón 1GL',
            'sku' => 'GAL-002',
            'cost' => 0.50,
            'price' => 1.00,
            'stock_qty' => 500,
            'manage_stock' => true,
            'low_stock' => 0,
            'status' => 'available',
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->plantWarehouse->id,
        ]);

        $rawMaterial = Product::create([
            'name' => 'Polietileno HDPE',
            'sku' => 'MAT-001',
            'cost' => 1.20,
            'price' => 1.50,
            'stock_qty' => 1000,
            'manage_stock' => true,
            'low_stock' => 0,
            'status' => 'available',
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->plantWarehouse->id,
        ]);

        // Create shift and production log
        $shift = Shift::create([
            'user_id' => $this->user->id,
            'warehouse_id' => $this->plantWarehouse->id,
            'operator_name' => 'Operador 1',
            'machine_name' => 'Sopladora 1',
            'start_time' => now()->subHours(5),
            'status' => 'open',
        ]);

        $pLog = ProductionLog::create([
            'shift_id' => $shift->id,
            'user_id' => $this->user->id,
            'status' => 'completed',
        ]);

        // Output: 300 1st quality galones
        ProductionOutput::create([
            'production_log_id' => $pLog->id,
            'product_id' => $product->id,
            'quantity' => 300,
            'quality' => '1st',
            'unit_cost' => 0.50,
            'total_cost' => 150.0,
            'created_at' => now(),
        ]);

        // Material consumption: 50kg HDPE
        ProductionMaterial::create([
            'production_log_id' => $pLog->id,
            'product_id' => $rawMaterial->id,
            'quantity' => 50,
            'unit_cost' => 1.20,
            'total_cost' => 60.0,
            'created_at' => now(),
        ]);

        // 1. Check Kardex for Produced Product
        $componentGalon = Livewire::test(ProductMovementsReport::class)
            ->set('product_id', $product->id)
            ->set('dateFrom', now()->subDays(1)->format('Y-m-d'))
            ->set('dateTo', now()->addDays(1)->format('Y-m-d'));

        $this->assertEquals(300.0, floatval($componentGalon->get('totalIn')));
        $movementsGalon = $componentGalon->instance()->getMovements();
        $this->assertTrue($movementsGalon->contains(function ($m) {
            return $m->type === 'Producción' && floatval($m->quantity_in) == 300.0;
        }));

        // 2. Check Kardex for Consumed Raw Material
        $componentMaterial = Livewire::test(ProductMovementsReport::class)
            ->set('product_id', $rawMaterial->id)
            ->set('dateFrom', now()->subDays(1)->format('Y-m-d'))
            ->set('dateTo', now()->addDays(1)->format('Y-m-d'));

        $this->assertEquals(50.0, floatval($componentMaterial->get('totalOut')));
        $movementsMat = $componentMaterial->instance()->getMovements();
        $this->assertTrue($movementsMat->contains(function ($m) {
            return $m->type === 'Consumo Producción' && floatval($m->quantity_out) == 50.0;
        }));
    }

    public function test_kardex_resets_balance_at_inventory_cut_and_calculates_subsequent_movements_cleanly()
    {
        $product = Product::create([
            'name' => 'Botellón 18.9 LTS Test',
            'sku' => 'BOT-TEST',
            'cost' => 2.00,
            'price' => 5.00,
            'stock_qty' => 0,
            'manage_stock' => true,
            'low_stock' => 0,
            'status' => 'available',
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->mainWarehouse->id,
        ]);

        $cutTime = Carbon::now()->subHours(2);
        
        // 1. Perform Cut
        $component = Livewire::test(ProductMovementsReport::class)
            ->set('product_id', $product->id)
            ->call('openCutModal');

        $cutWarehouses = [
            $this->mainWarehouse->id => [
                'name' => $this->mainWarehouse->name,
                'current_stock' => 0,
                'counted_stock' => 300,
            ],
            $this->plantWarehouse->id => [
                'name' => $this->plantWarehouse->name,
                'current_stock' => 0,
                'counted_stock' => 700,
            ],
        ];

        $component->set('cut_warehouses', $cutWarehouses)
            ->set('cut_notes', 'Toma Física')
            ->call('saveInventoryCut');

        // Backdate cut slightly to test subsequent movement
        $latestCut = \App\Models\InventoryCut::where('product_id', $product->id)->first();
        $latestCut->cut_date = $cutTime;
        $latestCut->save();

        // 2. Perform a Sale of 15 units after the cut
        $customer = \App\Models\Customer::create([
            'name' => 'Cliente Test',
            'taxpayer_id' => 'V-12345678-9',
            'phone' => '04141234567',
            'seller_id' => $this->user->id,
        ]);

        $sale = \App\Models\Sale::create([
            'user_id' => $this->user->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'FAC-TEST-001',
            'type' => 'cash',
            'status' => 'paid',
            'total' => 75.00,
            'total_usd' => 75.00,
            'items' => 1,
            'cash' => 75.00,
            'change' => 0.00,
            'discount' => 0.00,
            'primary_exchange_rate' => 1.0,
            'primary_currency_code' => 'USD',
            'created_at' => Carbon::now()->subHour(),
        ]);

        \App\Models\SaleDetail::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'warehouse_id' => $this->mainWarehouse->id,
            'quantity' => 15,
            'regular_price' => 5.00,
            'sale_price' => 5.00,
            'discount' => 0.00,
            'created_at' => Carbon::now()->subHour(),
        ]);

        // 3. Query Kardex for today
        $report = Livewire::test(ProductMovementsReport::class)
            ->set('product_id', $product->id)
            ->set('dateFrom', Carbon::now()->format('Y-m-d'))
            ->set('dateTo', Carbon::now()->format('Y-m-d'))
            ->set('selected_warehouse_id', 'all');

        $report->call('calculateMovements');
        $this->assertEquals(985.0, floatval($report->get('finalStock')));

        $movements = $report->instance()->getMovements();
        $this->assertCount(2, $movements);

        $firstMove = $movements->first();
        $this->assertEquals('Corte de Inventario', $firstMove->type);
        $this->assertEquals(1000.0, floatval($firstMove->quantity_in));
        $this->assertEquals(1, $firstMove->is_cut_reset);

        $secondMove = $movements->last();
        $this->assertEquals('Venta', $secondMove->type);
        $this->assertEquals(15.0, floatval($secondMove->quantity_out));

        // 4. Query Kardex for tomorrow (Cut baseline propagation)
        $reportTomorrow = Livewire::test(ProductMovementsReport::class)
            ->set('product_id', $product->id)
            ->set('dateFrom', Carbon::now()->addDay()->format('Y-m-d'))
            ->set('dateTo', Carbon::now()->addDay()->format('Y-m-d'))
            ->set('selected_warehouse_id', 'all');

        $reportTomorrow->call('calculateMovements');
        $this->assertEquals(985.0, floatval($reportTomorrow->get('initialStock')));
        $this->assertEquals(985.0, floatval($reportTomorrow->get('finalStock')));
    }

    public function test_product_movements_pdf_route_streams_correctly()
    {
        $product = Product::create([
            'name' => 'Galón 1GL PDF',
            'sku' => 'GAL-PDF',
            'cost' => 0.50,
            'price' => 1.00,
            'stock_qty' => 100,
            'manage_stock' => true,
            'low_stock' => 0,
            'status' => 'available',
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->mainWarehouse->id,
        ]);

        $response = $this->get(route('reports.product.movements.pdf', [
            'product_id' => $product->id,
            'dateFrom' => now()->startOfMonth()->format('Y-m-d'),
            'dateTo' => now()->format('Y-m-d'),
            'warehouse_id' => 'all',
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
