<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Tag;
use App\Models\Configuration;
use App\Models\Production;
use App\Models\Cargo;
use App\Mail\BagsProductionConsolidatedMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BagsProductionApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $warehouse;
    protected $category;
    protected $supplierMF;
    protected $supplierOther;
    protected $tagMF;
    protected $productMFByTag;
    protected $productMFBySupplier;
    protected $productOther;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.installed' => true]);

        // Mock LicenseService
        $this->mock(\App\Services\LicenseService::class, function ($mock) {
            $mock->shouldReceive('checkLicense')->andReturn([
                'status' => 'active',
                'days_remaining' => 30,
                'modules' => [],
                'max_devices' => 10,
            ]);
            $mock->shouldReceive('getClientId')->andReturn('test-client-id');
        });

        // Create warehouse
        $this->warehouse = Warehouse::create([
            'name' => 'Almacén de Bolsas Principal',
            'address' => 'Zona Franca 2',
            'is_active' => true,
        ]);

        // Create User
        $this->user = User::factory()->create([
            'name' => 'Operador Bolsas',
            'email' => 'operador.bolsas@example.com',
            'warehouse_id' => $this->warehouse->id,
        ]);

        // Create Category
        $this->category = Category::create([
            'name' => 'Bolsas de Polietileno',
        ]);

        // Create Suppliers
        $this->supplierMF = Supplier::create([
            'name' => 'M&F Steel SA',
            'taxpayer_id' => 'J-12345678-0',
            'address' => 'Supplier Address 1',
            'phone' => '12345678',
        ]);

        $this->supplierOther = Supplier::create([
            'name' => 'Proveedor General',
            'taxpayer_id' => 'J-87654321-0',
            'address' => 'Supplier Address 2',
            'phone' => '87654321',
        ]);

        // Create Tag M&F
        $this->tagMF = Tag::create([
            'name' => 'M&F',
        ]);

        // Create M&F Products
        $this->productMFByTag = Product::create([
            'sku' => 'BOL-22X60',
            'name' => 'Bolsa Baja Plana 22x60',
            'description' => 'Bolsa de polietileno baja plana',
            'cost' => 0.10,
            'price' => 0.25,
            'stock_qty' => 100,
            'low_stock' => 5,
            'manage_stock' => true,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplierOther->id,
            'status' => 1,
            'is_variable_quantity' => false,
        ]);
        $this->productMFByTag->tags()->attach($this->tagMF->id);

        $this->productMFBySupplier = Product::create([
            'sku' => 'BAM-BOBINA',
            'name' => 'Bobinas de Bambi',
            'description' => 'Bobina variable de plástico',
            'cost' => 2.50,
            'price' => 4.00,
            'stock_qty' => 10,
            'low_stock' => 1,
            'manage_stock' => true,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplierMF->id,
            'status' => 1,
            'is_variable_quantity' => true,
        ]);

        // Create normal non-bags product
        $this->productOther = Product::create([
            'sku' => 'OTHER-PROD',
            'name' => 'Articulo de Limpieza',
            'description' => 'Limpiador multiusos',
            'cost' => 1.00,
            'price' => 2.50,
            'stock_qty' => 50,
            'low_stock' => 5,
            'manage_stock' => true,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplierOther->id,
            'status' => 1,
            'is_variable_quantity' => false,
        ]);
    }

    public function test_get_products_only_lists_mf_products()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/bolsas/products');

        $response->assertStatus(200);

        // Should include the two M&F products but NOT the other product
        $response->assertJsonFragment(['sku' => 'BOL-22X60']);
        $response->assertJsonFragment(['sku' => 'BAM-BOBINA']);
        $response->assertJsonMissing(['sku' => 'OTHER-PROD']);
    }

    public function test_get_products_with_search_filter()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/bolsas/products?search=Bambi');

        $response->assertStatus(200);
        $response->assertJsonFragment(['sku' => 'BAM-BOBINA']);
        $response->assertJsonMissing(['sku' => 'BOL-22X60']);
    }

    public function test_store_bags_production_generates_pending_production_but_no_cargo()
    {
        // Setup configuration with bags warehouse
        Configuration::create([
            'business_name' => 'Fábrica Bolsas SA',
            'bolsas_warehouse_id' => $this->warehouse->id,
            'default_warehouse_id' => $this->warehouse->id,
        ]);

        $payload = [
            'production_date' => '2026-06-16',
            'notes' => 'Levantamiento de producción Miércoles',
            'details' => [
                [
                    'product_id' => $this->productMFByTag->id,
                    'quantity' => 20.00,
                    'weight' => 150.00,
                    'operator_name' => 'Juan',
                    'production_date' => '2026-06-16',
                ],
                [
                    'product_id' => $this->productMFBySupplier->id, // Variable
                    'quantity' => 2.00,
                    'weight' => 50.00,
                    'operator_name' => 'Javier',
                    'production_date' => '2026-06-16',
                    'metadata' => [
                        ['weight' => 25.00, 'color' => 'azul', 'batch' => 'L01'],
                        ['weight' => 25.00, 'color' => 'verde', 'batch' => 'L02'],
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/bolsas/production', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Levantamiento de producción registrado correctamente',
            ]);

        // Check database records
        $this->assertDatabaseHas('productions', [
            'production_date' => '2026-06-16',
            'status' => 'pending',
            'note' => 'Levantamiento de producción Miércoles',
        ]);

        // No Cargo should be created yet
        $this->assertDatabaseCount('cargos', 0);

        // Check details in db
        $this->assertDatabaseHas('production_details', [
            'product_id' => $this->productMFByTag->id,
            'quantity' => 20.00,
            'weight' => 150.00,
            'operator_name' => 'Juan',
            'production_date' => '2026-06-16',
        ]);

        $this->assertDatabaseHas('production_details', [
            'product_id' => $this->productMFBySupplier->id,
            'quantity' => 2.00,
            'weight' => 50.00,
            'operator_name' => 'Javier',
            'production_date' => '2026-06-16',
        ]);
    }

    public function test_production_history_lists_records_with_filters()
    {
        $production1 = Production::create([
            'user_id' => $this->user->id,
            'production_date' => '2026-06-10',
            'status' => 'sent',
            'note' => 'Notes 1',
        ]);

        $this->production_details1 = $production1->details()->create([
            'product_id' => $this->productMFByTag->id,
            'warehouse_id' => $this->warehouse->id,
            'material_type' => 'Original',
            'quantity' => 45.00,
            'weight' => 300.00,
            'operator_name' => 'Juan',
            'production_date' => '2026-06-10',
        ]);

        $production2 = Production::create([
            'user_id' => $this->user->id,
            'production_date' => '2026-06-11',
            'status' => 'pending',
            'note' => 'Notes 2',
        ]);

        $this->production_details2 = $production2->details()->create([
            'product_id' => $this->productMFBySupplier->id,
            'warehouse_id' => $this->warehouse->id,
            'material_type' => 'Original',
            'quantity' => 2.00,
            'weight' => 50.00,
            'operator_name' => 'Javier',
            'production_date' => '2026-06-11',
        ]);

        // 1. Unfiltered history
        $response = $this->actingAs($this->user)
            ->getJson('/api/bolsas/production/history');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data.data'));

        // 2. Filter by production date
        $response = $this->actingAs($this->user)
            ->getJson('/api/bolsas/production/history?production_date=2026-06-10');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));
        $this->assertEquals('Notes 1', $response->json('data.data.0.note'));

        // 3. Filter by operator name
        $response = $this->actingAs($this->user)
            ->getJson('/api/bolsas/production/history?operator_name=Javier');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));
        $this->assertEquals('Notes 2', $response->json('data.data.0.note'));
    }

    public function test_cargo_approval_marks_production_as_sent_and_sends_consolidated_email()
    {
        Mail::fake();

        // Setup configuration settings with email recipients
        $config = Configuration::create([
            'business_name' => 'Fábrica Bolsas SA',
            'default_warehouse_id' => $this->warehouse->id,
            'bolsas_warehouse_id' => $this->warehouse->id,
            'production_email_recipients' => ['supervisor@example.com'],
            'production_email_subject' => 'Reporte Producción de Bolsas - [FECHA]',
            'production_email_body' => 'Hola, resumen: [RESUMEN_DETALLES] peso total: [PESO_TOTAL] Kg',
        ]);

        // Create Production 1
        $production = Production::create([
            'user_id' => $this->user->id,
            'production_date' => '2026-06-16',
            'status' => 'pending',
        ]);

        $production->details()->create([
            'product_id' => $this->productMFByTag->id,
            'warehouse_id' => $this->warehouse->id,
            'material_type' => 'Original',
            'quantity' => 20.00,
            'weight' => 150.00,
            'operator_name' => 'Juan',
            'production_date' => '2026-06-16',
        ]);

        // Create Cargo 1 linked to Production 1
        $cargo = Cargo::create([
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->user->id,
            'motive' => 'Levantamiento de producción',
            'date' => '2026-06-16 00:00:00',
            'status' => 'pending',
            'production_id' => $production->id,
        ]);

        $cargo->details()->create([
            'product_id' => $this->productMFByTag->id,
            'quantity' => 20.00,
            'cost' => 0.10,
        ]);

        // Approve cargo (simulate auth)
        $cargosList = new \App\Livewire\Cargos\CargosList();
        $this->actingAs($this->user);
        
        // Setup permissions
        $this->user->givePermissionTo('adjustments.approve_cargo');

        $cargosList->approve($cargo->id);

        // Assert associated Production is updated to 'sent'
        $this->assertDatabaseHas('productions', [
            'id' => $production->id,
            'status' => 'sent',
        ]);

        // Assert Cargo is approved
        $this->assertDatabaseHas('cargos', [
            'id' => $cargo->id,
            'status' => 'approved',
        ]);

        // Assert consolidated email was sent since all cargos of the day are approved
        Mail::assertSent(BagsProductionConsolidatedMail::class, function ($mail) use ($production) {
            $this->assertTrue($mail->hasTo('supervisor@example.com'));
            $this->assertStringContainsString('Reporte Producción de Bolsas', $mail->subjectLine);
            $this->assertStringContainsString('150.00', $mail->bodyContent);
            $this->assertCount(1, $mail->pdfs); // Should attach one PDF
            $this->assertEquals('produccion_bolsas_2026-06-16_lote_' . $production->id . '.pdf', $mail->pdfs[0]['name']);
            return true;
        });
    }

    public function test_web_planilla_approval_creates_pending_cargo_without_updating_stock()
    {
        // Setup configuration with bags warehouse
        Configuration::create([
            'business_name' => 'Fábrica Bolsas SA',
            'bolsas_warehouse_id' => $this->warehouse->id,
            'default_warehouse_id' => $this->warehouse->id,
        ]);

        // Create Production in state pending
        $production = Production::create([
            'user_id' => $this->user->id,
            'production_date' => '2026-06-16',
            'status' => 'pending',
        ]);

        $production->details()->create([
            'product_id' => $this->productMFByTag->id,
            'warehouse_id' => $this->warehouse->id,
            'material_type' => 'Original',
            'quantity' => 20.00,
            'weight' => 150.00,
            'operator_name' => 'Juan',
            'production_date' => '2026-06-16',
        ]);

        // Capture initial stock quantities
        $initialGlobalStock = $this->productMFByTag->stock_qty;
        
        $initialPivotStock = 0;
        $pivot = $this->productMFByTag->warehouses()->where('warehouse_id', $this->warehouse->id)->first();
        if ($pivot) {
            $initialPivotStock = $pivot->pivot->stock_qty;
        }

        // Setup authentication
        $this->actingAs($this->user);

        // Approve production on the web (simulate clicking sendToCargo in ProductionList)
        $productionList = new \App\Livewire\Production\ProductionList();
        $productionList->sendToCargo($production->id);

        // Assert production status is now 'approved'
        $this->assertDatabaseHas('productions', [
            'id' => $production->id,
            'status' => 'approved',
        ]);

        // Assert pending Cargo has been created
        $this->assertDatabaseHas('cargos', [
            'production_id' => $production->id,
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
        ]);

        // Assert stock has NOT changed
        $productReloaded = $this->productMFByTag->fresh();
        $this->assertEquals($initialGlobalStock, $productReloaded->stock_qty);
        
        $pivotReloaded = $productReloaded->warehouses()->where('warehouse_id', $this->warehouse->id)->first();
        $reloadedPivotStock = $pivotReloaded ? $pivotReloaded->pivot->stock_qty : 0;
        $this->assertEquals($initialPivotStock, $reloadedPivotStock);

        // Assert no ProductItems (bobinas) were created
        $this->assertDatabaseCount('product_items', 0);
    }

    public function test_cargo_approval_creates_product_items_with_production_date_and_operator_name()
    {
        Mail::fake();

        // Create Production
        $production = Production::create([
            'user_id' => $this->user->id,
            'production_date' => '2026-06-16',
            'status' => 'pending',
        ]);

        $production->details()->create([
            'product_id' => $this->productMFBySupplier->id, // Variable
            'warehouse_id' => $this->warehouse->id,
            'material_type' => 'Original',
            'quantity' => 2.00,
            'weight' => 50.00,
            'operator_name' => 'Javier',
            'production_date' => '2026-06-16',
            'metadata' => [
                ['weight' => 25.00, 'color' => 'azul', 'batch' => 'L01'],
                ['weight' => 25.00, 'color' => 'verde', 'batch' => 'L02'],
            ]
        ]);

        // Send to cargo
        $productionList = new \App\Livewire\Production\ProductionList();
        $this->actingAs($this->user);
        $productionList->sendToCargo($production->id);

        $cargo = Cargo::where('production_id', $production->id)->firstOrFail();

        // Approve cargo
        $cargosList = new \App\Livewire\Cargos\CargosList();
        $this->user->givePermissionTo('adjustments.approve_cargo');
        $cargosList->approve($cargo->id);

        // Assert ProductItems were created with correct production_date and operator_name
        $this->assertDatabaseHas('product_items', [
            'product_id' => $this->productMFBySupplier->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 25.00,
            'color' => 'azul',
            'batch' => 'L01',
            'production_date' => '2026-06-16',
            'operator_name' => 'Javier',
        ]);

        $this->assertDatabaseHas('product_items', [
            'product_id' => $this->productMFBySupplier->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 25.00,
            'color' => 'verde',
            'batch' => 'L02',
            'production_date' => '2026-06-16',
            'operator_name' => 'Javier',
        ]);
    }

    public function test_web_edit_production_preserves_multiple_details_of_same_product()
    {
        // 1. Create a pending production with two details for the same product
        $production = Production::create([
            'user_id' => $this->user->id,
            'production_date' => '2026-06-16',
            'status' => 'pending',
        ]);

        $production->details()->create([
            'product_id' => $this->productMFBySupplier->id,
            'warehouse_id' => $this->warehouse->id,
            'material_type' => 'Original',
            'quantity' => 1.00,
            'weight' => 26.00,
            'operator_name' => 'Javier',
            'production_date' => '2026-06-16',
            'metadata' => [
                ['weight' => 26.00, 'color' => '', 'batch' => '']
            ]
        ]);

        $production->details()->create([
            'product_id' => $this->productMFBySupplier->id,
            'warehouse_id' => $this->warehouse->id,
            'material_type' => 'Original',
            'quantity' => 1.00,
            'weight' => 27.00,
            'operator_name' => 'Javier',
            'production_date' => '2026-06-16',
            'metadata' => [
                ['weight' => 27.00, 'color' => '', 'batch' => '']
            ]
        ]);

        $this->assertEquals(2, $production->fresh()->details()->count());

        // 2. Test the Livewire component editing this production
        $this->actingAs($this->user);

        \Livewire\Livewire::test(\App\Livewire\Production\CreateProduction::class, ['production' => $production->id])
            ->assertSet('isEdit', true)
            ->assertSet('productionId', $production->id)
            // Assert both details are loaded into the cart as separate rows
            ->assertCount('cart', 2)
            // Call save to update and persist
            ->call('save');

        // 3. Verify that after updating, the production still has both details in the DB
        $productionFresh = $production->fresh();
        $this->assertEquals(2, $productionFresh->details()->count());

        $this->assertDatabaseHas('production_details', [
            'production_id' => $production->id,
            'weight' => 26.00,
        ]);

        $this->assertDatabaseHas('production_details', [
            'production_id' => $production->id,
            'weight' => 27.00,
        ]);
    }

    public function test_web_edit_cargo_preserves_multiple_details_of_same_product()
    {
        // 1. Create a pending Cargo with two details for the same product
        $cargo = Cargo::create([
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->user->id,
            'motive' => 'Test Cargo',
            'authorized_by' => 'Supervisor',
            'date' => now(),
            'status' => 'pending',
        ]);

        $cargo->details()->create([
            'product_id' => $this->productMFBySupplier->id,
            'quantity' => 26.00,
            'cost' => 2.50,
            'items_json' => json_encode([
                ['weight' => 26.00, 'color' => '', 'batch' => '']
            ])
        ]);

        $cargo->details()->create([
            'product_id' => $this->productMFBySupplier->id,
            'quantity' => 27.00,
            'cost' => 2.50,
            'items_json' => json_encode([
                ['weight' => 27.00, 'color' => '', 'batch' => '']
            ])
        ]);

        $this->assertEquals(2, $cargo->fresh()->details()->count());

        // 2. Test the Livewire component editing this Cargo
        $this->actingAs($this->user);

        \Livewire\Livewire::test(\App\Livewire\Cargos\CreateCargo::class, ['cargo' => $cargo->id])
            ->assertSet('cargo_id', $cargo->id)
            // Assert both details are loaded into the cart as separate rows
            ->assertCount('cart', 2)
            // Call save to update and persist
            ->call('save');

        // 3. Verify that after updating, the Cargo still has both details in the DB
        $cargoFresh = $cargo->fresh();
        $this->assertEquals(2, $cargoFresh->details()->count());

        $this->assertDatabaseHas('cargo_details', [
            'cargo_id' => $cargo->id,
            'quantity' => 26.00,
        ]);

        $this->assertDatabaseHas('cargo_details', [
            'cargo_id' => $cargo->id,
            'quantity' => 27.00,
        ]);
    }

    public function test_production_cost_flow_and_consolidated_email()
    {
        Mail::fake();

        // 1. Setup config and product with initial cost
        $config = Configuration::create([
            'business_name' => 'Fábrica Bolsas Test',
            'bolsas_warehouse_id' => $this->warehouse->id,
            'default_warehouse_id' => $this->warehouse->id,
            'production_email_recipients' => ['boss@example.com'],
        ]);

        $this->productMFByTag->update(['cost' => 0.15]);

        // 2. Store via API
        $payload = [
            'production_date' => '2026-06-22',
            'notes' => 'Test cost flow',
            'details' => [
                [
                    'product_id' => $this->productMFByTag->id,
                    'quantity' => 10.00,
                    'weight' => 10.00,
                    'operator_name' => 'Gomez',
                    'production_date' => '2026-06-22',
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/bolsas/production', $payload);

        $response->assertStatus(200);

        // Assert cost was saved as 0.15
        $production = Production::orderBy('id', 'desc')->firstOrFail();
        $this->assertEquals(0.15, floatval($production->details->first()->cost));

        // 3. Change product cost in catalog
        $this->productMFByTag->update(['cost' => 0.18]);

        // 4. Test web edit updates the cost from catalog because production is pending
        \Livewire\Livewire::test(\App\Livewire\Production\CreateProduction::class, ['production' => $production->id])
            ->assertSet('cart.detail_' . $production->details->first()->id . '.cost', 0.18)
            ->call('save');

        // Confirm saved cost is updated to 0.18 in details
        $detail = $production->fresh()->details->first();
        $this->assertEquals(0.18, floatval($detail->cost));

        // 5. Change product cost again in catalog (to check inmutability)
        $this->productMFByTag->update(['cost' => 0.22]);

        // 6. Send to cargo (approving production, creating cargo)
        $productionList = new \App\Livewire\Production\ProductionList();
        $productionList->sendToCargo($production->id);

        $cargo = Cargo::where('production_id', $production->id)->firstOrFail();
        
        // Assert cargo detail cost copies the historical cost of the production detail (0.18), not the current catalog cost (0.22)
        $this->assertDatabaseHas('cargo_details', [
            'cargo_id' => $cargo->id,
            'product_id' => $this->productMFByTag->id,
            'cost' => 0.18
        ]);

        // 7. Approve Cargo to trigger consolidated email
        $cargosList = new \App\Livewire\Cargos\CargosList();
        $this->user->givePermissionTo('adjustments.approve_cargo');
        
        // Ensure there is another unrelated pending cargo on the same day to make sure it doesn't block this email!
        $unrelatedProduction = Production::create([
            'user_id' => $this->user->id,
            'production_date' => now(),
            'status' => 'pending',
        ]);

        $unrelatedCargo = Cargo::create([
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->user->id,
            'motive' => 'Unrelated pending cargo',
            'date' => now(),
            'status' => 'pending',
            'production_id' => $unrelatedProduction->id,
        ]);

        $cargosList->approve($cargo->id);

        // Assert associated Production status is updated to 'sent'
        $this->assertEquals('sent', $production->fresh()->status);

        // Assert consolidated email was sent because the single cargo of this production was approved (unrelated cargo 99999 being pending did not block it!)
        Mail::assertSent(BagsProductionConsolidatedMail::class, function ($mail) use ($production) {
            $this->assertTrue($mail->hasTo('boss@example.com'));
            $date = \Carbon\Carbon::now()->format('d/m/Y');
            $expectedSubject = "Planilla de Levantamiento de la Fábrica de Bolsas - Lote #{$production->id} - {$date}";
            $this->assertEquals($expectedSubject, $mail->subjectLine);
            $this->assertStringContainsString("Lote(s) de Producción: #{$production->id}", $mail->bodyContent);
            return true;
        });
    }
}
