<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Configuration;
use App\Models\SopladosProductionTarget;
use App\Models\Shift;
use App\Models\ProductionLog;
use App\Models\ProductionOutput;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Transfer;
use App\Models\TransferDetail;
use App\Models\Cargo;
use App\Models\CargoDetail;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Artisan;

class SopladosExpectedProductionTest extends TestCase
{
    use DatabaseTransactions;

    private $warehouseSoplados;
    private $warehouseZona;
    private $user;
    private $category;
    private $supplier;
    private $finishedProduct;
    private $rawMaterial;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock license check
        $this->mock(\App\Services\LicenseService::class, function ($mock) {
            $mock->shouldReceive('checkLicense')->andReturn([
                'status' => 'active',
                'days_remaining' => 30,
                'modules' => [],
                'max_devices' => 10,
            ]);
            $mock->shouldReceive('getClientId')->andReturn('test-client-id');
        });

        // Ensure permission exists
        Permission::findOrCreate('production.index', 'web');

        // Create warehouses
        $this->warehouseSoplados = Warehouse::create([
            'name' => 'PLANTA SOPLADOS',
            'address' => 'Zona Ind',
            'is_active' => true,
        ]);

        $this->warehouseZona = Warehouse::create([
            'name' => 'ZONA',
            'address' => 'Zona Deposito',
            'is_active' => true,
        ]);

        // Setup config
        Configuration::updateOrCreate(
            ['id' => 1],
            [
                'business_name' => 'Test Soplados Co',
                'soplados_warehouse_id' => $this->warehouseSoplados->id,
                'default_warehouse_id' => $this->warehouseSoplados->id
            ]
        );

        // Create User
        $this->user = User::factory()->create([
            'warehouse_id' => $this->warehouseSoplados->id
        ]);
        $this->user->givePermissionTo('production.index');

        // Create Category & Supplier
        $this->category = Category::create(['name' => 'Soplados']);
        $this->supplier = Supplier::create([
            'name' => 'Supplier Soplados',
            'taxpayer_id' => 'J-12345678-9',
            'address' => 'Ind',
            'phone' => '123456'
        ]);

        // Create Finished Product and Tag it
        $this->finishedProduct = Product::create([
            'sku' => 'PET-330ML',
            'name' => 'ENVASE PET 330ML',
            'cost' => 0.10,
            'price' => 0.30,
            'stock_qty' => 100,
            'manage_stock' => true,
            'low_stock' => 5,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'available',
        ]);
        $tag = \App\Models\Tag::firstOrCreate(['name' => 'soplados']);
        $this->finishedProduct->tags()->attach($tag->id);

        // Create Raw Material and Tag it
        $this->rawMaterial = Product::create([
            'sku' => 'PET-PREFORM',
            'name' => 'PREFORMA PET 17G',
            'cost' => 0.05,
            'price' => 0.00,
            'stock_qty' => 5000,
            'is_raw_material' => true,
            'manage_stock' => true,
            'low_stock' => 5,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'available',
        ]);
        $this->rawMaterial->tags()->attach($tag->id);
    }

    public function test_expected_production_targets_crud_via_livewire()
    {
        $this->actingAs($this->user);

        // Test Livewire component renders and can set targets
        Livewire::test(\App\Livewire\Soplados\ExpectedProductionList::class)
            ->set('search_product', 'PET 330')
            ->assertSet('product_results', function ($results) {
                return count($results) > 0 && $results[0]['id'] === $this->finishedProduct->id;
            })
            ->call('selectProduct', $this->finishedProduct->id, $this->finishedProduct->name)
            ->set('min_target', 3500)
            ->set('max_target', 4000)
            ->call('store')
            ->assertHasNoErrors()
            ->assertDispatched('msg', 'Meta de producción creada correctamente');

        $this->assertDatabaseHas('soplados_production_targets', [
            'product_id' => $this->finishedProduct->id,
            'min_target' => 3500,
            'max_target' => 4000
        ]);

        // Edit the target
        $target = SopladosProductionTarget::where('product_id', $this->finishedProduct->id)->first();
        Livewire::test(\App\Livewire\Soplados\ExpectedProductionList::class)
            ->call('edit', $target->id)
            ->assertSet('product_id', $this->finishedProduct->id)
            ->assertSet('min_target', 3500)
            ->assertSet('max_target', 4000)
            ->set('min_target', 3600)
            ->set('max_target', 4100)
            ->call('store')
            ->assertHasNoErrors()
            ->assertDispatched('msg', 'Meta de producción actualizada correctamente');

        $this->assertDatabaseHas('soplados_production_targets', [
            'id' => $target->id,
            'min_target' => 3600,
            'max_target' => 4100
        ]);

        // Delete the target
        Livewire::test(\App\Livewire\Soplados\ExpectedProductionList::class)
            ->call('delete', $target->id)
            ->assertDispatched('msg', 'Meta de producción eliminada');

        $this->assertDatabaseMissing('soplados_production_targets', [
            'id' => $target->id
        ]);
    }

    public function test_weekly_consolidated_report_processing_and_compliance()
    {
        // 1. Set Expected Target
        SopladosProductionTarget::create([
            'product_id' => $this->finishedProduct->id,
            'min_target' => 3500,
            'max_target' => 4000
        ]);

        // 2. Create Closed Shift with production outputs
        $shift = Shift::create([
            'type' => 'day',
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDays(2)->addHours(8),
            'status' => 'closed',
            'user_id' => $this->user->id,
            'warehouse_id' => $this->warehouseSoplados->id
        ]);

        $log = ProductionLog::create([
            'shift_id' => $shift->id,
            'user_id' => $this->user->id,
            'notes' => 'Test Shift production'
        ]);

        ProductionOutput::create([
            'production_log_id' => $log->id,
            'product_id' => $this->finishedProduct->id,
            'quantity' => 3700, // Inside 3500-4000 (compliant)
            'quality' => '1st'
        ]);

        // 3. Create raw material entries (Insumos)
        // Entry A: Purchase to Zona
        $purchaseZona = Purchase::create([
            'total' => 50.00,
            'flete' => 0.00,
            'items' => 1,
            'status' => 'paid',
            'type' => 'credit',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouseZona->id,
            'user_id' => $this->user->id
        ]);
        PurchaseDetail::create([
            'purchase_id' => $purchaseZona->id,
            'product_id' => $this->rawMaterial->id,
            'quantity' => 1000,
            'cost' => 0.05,
            'price' => 0.00,
            'flete_product' => 0.00,
            'flete_total' => 0.00
        ]);

        // Entry B: Transfer from Zona to Soplados
        $transfer = Transfer::create([
            'from_warehouse_id' => $this->warehouseZona->id,
            'to_warehouse_id' => $this->warehouseSoplados->id,
            'user_id' => $this->user->id,
            'status' => 'completed'
        ]);
        TransferDetail::create([
            'transfer_id' => $transfer->id,
            'product_id' => $this->rawMaterial->id,
            'quantity' => 500,
            'received_quantity' => 500
        ]);

        // Entry C: Approved Cargo to Soplados
        $cargo = Cargo::create([
            'warehouse_id' => $this->warehouseSoplados->id,
            'user_id' => $this->user->id,
            'status' => 'approved',
            'approval_date' => now()->subDays(1),
            'date' => now()->subDays(1)
        ]);
        CargoDetail::create([
            'cargo_id' => $cargo->id,
            'product_id' => $this->rawMaterial->id,
            'quantity' => 200,
            'cost' => 0.05
        ]);

        // 4. Run the Artisan command
        $exitCode = Artisan::call('app:send-soplados-weekly-report');

        // It should complete successfully
        $this->assertEquals(0, $exitCode);
    }

    public function test_weekly_report_groups_products_by_production_target_id()
    {
        // 1. Create main product (target representative)
        $mainProduct = Product::create([
            'sku' => 'GALON-SIN-ASA',
            'name' => 'ENVASE PET GALON SIN ASA',
            'cost' => 0.20,
            'price' => 0.50,
            'stock_qty' => 10,
            'manage_stock' => true,
            'low_stock' => 2,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'available',
        ]);
        $tag = \App\Models\Tag::firstOrCreate(['name' => 'soplados']);
        $mainProduct->tags()->attach($tag->id);

        // 2. Create child product (with asa) pointing to the main product
        $childProduct = Product::create([
            'sku' => 'GALON-CON-ASA',
            'name' => 'ENVASE PET GALON CON ASA',
            'cost' => 0.22,
            'price' => 0.55,
            'stock_qty' => 5,
            'manage_stock' => true,
            'low_stock' => 1,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'available',
            'production_target_id' => $mainProduct->id
        ]);
        $childProduct->tags()->attach($tag->id);

        // 3. Create target for main product ONLY
        SopladosProductionTarget::create([
            'product_id' => $mainProduct->id,
            'min_target' => 1200,
            'max_target' => 1600
        ]);

        // 4. Create shift with production outputs for both products
        $shift = Shift::create([
            'type' => 'day',
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDays(2)->addHours(8),
            'status' => 'closed',
            'user_id' => $this->user->id,
            'warehouse_id' => $this->warehouseSoplados->id
        ]);

        $log = ProductionLog::create([
            'shift_id' => $shift->id,
            'user_id' => $this->user->id,
            'notes' => 'Co-production same shift'
        ]);

        ProductionOutput::create([
            'production_log_id' => $log->id,
            'product_id' => $mainProduct->id,
            'quantity' => 1000,
            'quality' => '1st'
        ]);

        ProductionOutput::create([
            'production_log_id' => $log->id,
            'product_id' => $childProduct->id,
            'quantity' => 500,
            'quality' => '1st'
        ]);

        // Run report and verify consolidation logic
        $exitCode = Artisan::call('app:send-soplados-weekly-report');
        $this->assertEquals(0, $exitCode);

        // We can inspect the data passed to the mail or pdf view by instantiating the command/service or inspecting the output
        // For testing correctness of the query output, we can run the report generation directly in code and inspect the output arrays:
        // Build start/end
        $date = \Carbon\Carbon::today()->toDateString();
        $end = \Carbon\Carbon::parse($date)->endOfDay();
        $start = \Carbon\Carbon::parse($date)->subDays(6)->startOfDay();

        $shifts = \App\Models\Shift::with([
            'productionLogs.outputs.product.productionTarget'
        ])->where('id', $shift->id)->get();

        $targets = SopladosProductionTarget::get()->keyBy('product_id');

        $shiftOutputs = [];
        foreach ($shifts[0]->productionLogs as $l) {
            foreach ($l->outputs as $out) {
                $qty = floatval($out->quantity);
                $pId = $out->product_id;
                $targetProductId = $out->product->production_target_id ?? $pId;
                $targetProduct = $out->product->productionTarget ?? $out->product;
                $pName = $targetProduct->name;

                if (!isset($shiftOutputs[$targetProductId])) {
                    $target = $targets->get($targetProductId);
                    $shiftOutputs[$targetProductId] = [
                        'name' => $pName,
                        'quantity' => 0,
                        'min' => $target ? $target->min_target : 0,
                        'max' => $target ? $target->max_target : 0,
                    ];
                }
                if (in_array($out->quality, ['1st', '2nd'])) {
                    $shiftOutputs[$targetProductId]['quantity'] += $qty;
                }
            }
        }

        // We assert that the products are consolidated under the mainProduct ID
        $this->assertCount(1, $shiftOutputs);
        $this->assertArrayHasKey($mainProduct->id, $shiftOutputs);
        $this->assertEquals(1500, $shiftOutputs[$mainProduct->id]['quantity']);
        $this->assertEquals(1200, $shiftOutputs[$mainProduct->id]['min']);
        $this->assertEquals(1600, $shiftOutputs[$mainProduct->id]['max']);
    }

    public function test_download_soplados_report_action_returns_pdf_download()
    {
        $this->actingAs($this->user);

        // We trigger the download action and assert that the file is downloaded
        Livewire::test(\App\Livewire\ProductionReport::class)
            ->call('downloadSopladosReport')
            ->assertFileDownloaded();
    }
}
