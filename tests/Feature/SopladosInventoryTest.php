<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Category;
use App\Models\Configuration;
use App\Models\ProductWarehouse;
use App\Models\SopladosInventory;
use App\Models\SopladosInventoryDetail;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class SopladosInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected $supervisor;
    protected $operator;
    protected $warehouse;
    protected $category;
    protected $finishedProduct;
    protected $secondQualityProduct;
    protected $rawMaterial;

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

        // Forget cached permissions and create them
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('soplados.manager', 'web');
        Permission::findOrCreate('soplados.operator', 'web');

        // Create warehouse
        $this->warehouse = Warehouse::create([
            'name' => 'Planta Soplados Test',
            'address' => 'Planta Address',
            'is_active' => true,
        ]);

        // Set Soplados warehouse configuration
        Configuration::create([
            'business_name' => 'Soplados SA',
            'soplados_warehouse_id' => $this->warehouse->id,
            'default_warehouse_id' => $this->warehouse->id,
        ]);

        // Create Users
        $this->supervisor = User::factory()->create([
            'name' => 'Supervisor Pedro',
            'email' => 'pedro@example.com',
            'warehouse_id' => $this->warehouse->id,
        ]);
        $this->supervisor->givePermissionTo('soplados.manager');

        $this->operator = User::factory()->create([
            'name' => 'Operario Luis',
            'email' => 'luis@example.com',
            'warehouse_id' => $this->warehouse->id,
        ]);
        $this->operator->givePermissionTo('soplados.operator');

        // Create Category
        $this->category = Category::create([
            'name' => 'Envases Plásticos',
        ]);

        // Create Supplier
        $supplier = \App\Models\Supplier::create([
            'name' => 'Supplier Test',
            'taxpayer_id' => 'J-11111111-1',
            'address' => 'Supplier Address',
            'phone' => '12345678',
        ]);

        // Create Products
        $this->secondQualityProduct = Product::create([
            'sku' => 'BOT-5L-2DA',
            'name' => 'Botellon 5L Segunda',
            'cost' => 0.30,
            'price' => 0.80,
            'stock_qty' => 50,
            'low_stock' => 0,
            'manage_stock' => false,
            'category_id' => $this->category->id,
            'supplier_id' => $supplier->id,
            'status' => 1,
        ]);

        $this->finishedProduct = Product::create([
            'sku' => 'BOT-5L-1RA',
            'name' => 'Botellon 5L Primera',
            'cost' => 0.50,
            'price' => 1.50,
            'stock_qty' => 100,
            'low_stock' => 0,
            'manage_stock' => false,
            'second_quality_product_id' => $this->secondQualityProduct->id,
            'category_id' => $this->category->id,
            'supplier_id' => $supplier->id,
            'status' => 1,
        ]);

        // Tag finished product with 'soplados'
        $tag = \App\Models\Tag::firstOrCreate(['name' => 'soplados']);
        $this->finishedProduct->tags()->attach($tag->id);

        $this->rawMaterial = Product::create([
            'sku' => 'MAT-PREF',
            'name' => 'Preforma PET',
            'cost' => 0.10,
            'price' => 0.00,
            'stock_qty' => 500,
            'low_stock' => 0,
            'manage_stock' => false,
            'is_raw_material' => true,
            'category_id' => $this->category->id,
            'supplier_id' => $supplier->id,
            'status' => 1,
        ]);

        $this->rawMaterial->tags()->attach($tag->id);

        // Add formula to link finishedProduct and rawMaterial
        \App\Models\ProductionFormula::create([
            'product_id' => $this->finishedProduct->id,
            'ingredient_id' => $this->rawMaterial->id,
            'quantity' => 1.0,
        ]);

        // Seed initial stocks in warehouse
        ProductWarehouse::create([
            'product_id' => $this->finishedProduct->id,
            'warehouse_id' => $this->warehouse->id,
            'stock_qty' => 100.0,
        ]);

        ProductWarehouse::create([
            'product_id' => $this->secondQualityProduct->id,
            'warehouse_id' => $this->warehouse->id,
            'stock_qty' => 50.0,
        ]);

        ProductWarehouse::create([
            'product_id' => $this->rawMaterial->id,
            'warehouse_id' => $this->warehouse->id,
            'stock_qty' => 500.0,
        ]);
    }

    public function test_unauthorized_user_cannot_access_inventory_products()
    {
        $unauthorized = User::factory()->create();

        $response = $this->actingAs($unauthorized)
            ->getJson('/api/soplados/inventory/products');

        $response->assertStatus(403);
    }

    public function test_supervisor_can_get_products_for_count_with_theoretical_stocks()
    {
        $response = $this->actingAs($this->supervisor)
            ->getJson('/api/soplados/inventory/products');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'warehouse' => 'Planta Soplados Test',
            ]);

        $products = $response->json('products');
        
        $this->assertCount(2, $products); // 1 finished product + 1 raw material (formula ingredient)
        
        $finished = collect($products)->where('type', 'finished_product')->first();
        $this->assertEquals('Botellon 5L Primera', $finished['name']);
        $this->assertEquals(100.0, $finished['system_stock_primera']);
        $this->assertEquals(50.0, $finished['system_stock_segunda']);
        $this->assertEquals($this->secondQualityProduct->id, $finished['production_target_id']);

        $material = collect($products)->where('type', 'raw_material')->first();
        $this->assertEquals('Preforma PET', $material['name']);
        $this->assertEquals(500.0, $material['system_stock_primera']);
    }

    public function test_supervisor_can_store_initial_count_but_stock_remains_unchanged()
    {
        $payload = [
            'notes' => 'Conteo de fin de mes',
            'products' => [
                [
                    'id' => $this->finishedProduct->id,
                    'type' => 'finished_product',
                    'counted_primera' => 95.0, // System has 100
                    'counted_segunda' => 48.0, // System has 50
                    'counted_merma' => 2.0,
                ],
                [
                    'id' => $this->rawMaterial->id,
                    'type' => 'raw_material',
                    'counted_primera' => 490.0, // System has 500
                ]
            ]
        ];

        $response = $this->actingAs($this->supervisor)
            ->postJson('/api/soplados/inventory', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Inventario registrado y enviado para conformidad del operador.'
            ]);

        // Assert SopladosInventory record is created in pending_acceptance state
        $this->assertDatabaseHas('soplados_inventories', [
            'supervisor_id' => $this->supervisor->id,
            'status' => 'pending_acceptance',
            'notes' => 'Conteo de fin de mes',
        ]);

        // Assert details are stored correctly with computed differences
        $this->assertDatabaseHas('soplados_inventory_details', [
            'product_id' => $this->finishedProduct->id,
            'type' => 'finished_product',
            'system_stock_primera' => 100.0,
            'counted_primera' => 95.0,
            'difference_primera' => -5.0,
            'system_stock_segunda' => 50.0,
            'counted_segunda' => 48.0,
            'difference_segunda' => -2.0,
            'counted_merma' => 2.0,
        ]);

        $this->assertDatabaseHas('soplados_inventory_details', [
            'product_id' => $this->rawMaterial->id,
            'type' => 'raw_material',
            'system_stock_primera' => 500.0,
            'counted_primera' => 490.0,
            'difference_primera' => -10.0,
        ]);

        // CRITICAL CHECK: Warehouse stocks must NOT have changed yet!
        $this->assertEquals(100.0, ProductWarehouse::where('product_id', $this->finishedProduct->id)->first()->stock_qty);
        $this->assertEquals(50.0, ProductWarehouse::where('product_id', $this->secondQualityProduct->id)->first()->stock_qty);
        $this->assertEquals(500.0, ProductWarehouse::where('product_id', $this->rawMaterial->id)->first()->stock_qty);
    }

    public function test_operator_can_view_pending_inventories_and_accept_them_which_triggers_stock_adjustment()
    {
        // 1. Create a pending inventory count
        $inventory = SopladosInventory::create([
            'warehouse_id' => $this->warehouse->id,
            'supervisor_id' => $this->supervisor->id,
            'status' => 'pending_acceptance',
            'notes' => 'Observaciones supervisor',
        ]);

        SopladosInventoryDetail::create([
            'soplados_inventory_id' => $inventory->id,
            'product_id' => $this->finishedProduct->id,
            'type' => 'finished_product',
            'system_stock_primera' => 100.0,
            'counted_primera' => 95.0,
            'difference_primera' => -5.0,
            'system_stock_segunda' => 50.0,
            'counted_segunda' => 48.0,
            'difference_segunda' => -2.0,
            'counted_merma' => 2.0,
        ]);

        SopladosInventoryDetail::create([
            'soplados_inventory_id' => $inventory->id,
            'product_id' => $this->rawMaterial->id,
            'type' => 'raw_material',
            'system_stock_primera' => 500.0,
            'counted_primera' => 490.0,
            'difference_primera' => -10.0,
        ]);

        // 2. Fetch pending inventories as operator
        $pendingResponse = $this->actingAs($this->operator)
            ->getJson('/api/soplados/inventory/pending');

        $pendingResponse->assertStatus(200)
            ->assertJsonCount(1, 'inventories');

        // 3. Accept conformity as operator
        $acceptResponse = $this->actingAs($this->operator)
            ->postJson("/api/soplados/inventory/{$inventory->id}/accept", [
                'operator_notes' => 'Conforme, recontado con el supervisor.',
            ]);

        $acceptResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Inventario aceptado y stock ajustado en el sistema.'
            ]);

        // 4. Assert inventory status changed to completed in database
        $this->assertDatabaseHas('soplados_inventories', [
            'id' => $inventory->id,
            'status' => 'completed',
            'operator_id' => $this->operator->id,
            'operator_notes' => 'Conforme, recontado con el supervisor.',
        ]);

        // 5. CRITICAL CHECK: Warehouse stocks MUST have updated to the counted quantities!
        $this->assertEquals(95.0, ProductWarehouse::where('product_id', $this->finishedProduct->id)->where('warehouse_id', $this->warehouse->id)->first()->stock_qty);
        $this->assertEquals(48.0, ProductWarehouse::where('product_id', $this->secondQualityProduct->id)->where('warehouse_id', $this->warehouse->id)->first()->stock_qty);
        $this->assertEquals(490.0, ProductWarehouse::where('product_id', $this->rawMaterial->id)->where('warehouse_id', $this->warehouse->id)->first()->stock_qty);

        // Check default warehouse sync (since warehouse is configured as default)
        $this->assertEquals(95.0, Product::find($this->finishedProduct->id)->stock_qty);
        $this->assertEquals(48.0, Product::find($this->secondQualityProduct->id)->stock_qty);
        $this->assertEquals(490.0, Product::find($this->rawMaterial->id)->stock_qty);
    }

    public function test_formulas_component_filters_products_and_ingredients()
    {
        // 1. We create another raw material and another finished product to test searching
        $tag = \App\Models\Tag::where('name', 'soplados')->first();
        
        $otherFinished = Product::create([
            'sku' => 'BOT-3L-1RA',
            'name' => 'Envase 3L Primera',
            'cost' => 0.40,
            'price' => 1.20,
            'stock_qty' => 10,
            'low_stock' => 0,
            'manage_stock' => false,
            'is_raw_material' => false,
            'category_id' => $this->category->id,
            'supplier_id' => $this->finishedProduct->supplier_id,
            'status' => 1,
        ]);
        $otherFinished->tags()->attach($tag->id);

        $otherMaterial = Product::create([
            'sku' => 'MAT-TAPA',
            'name' => 'Tapa Rosca',
            'cost' => 0.02,
            'price' => 0.00,
            'stock_qty' => 1000,
            'low_stock' => 0,
            'manage_stock' => false,
            'is_raw_material' => true,
            'category_id' => $this->category->id,
            'supplier_id' => $this->finishedProduct->supplier_id,
            'status' => 1,
        ]);
        $otherMaterial->tags()->attach($tag->id);

        // 2. Test the Livewire Formulas component
        // Search finished products - should return 'Envase 3L Primera' (since it is NOT raw material)
        // and NOT 'Tapa Rosca' (since it IS raw material)
        Livewire::test(\App\Livewire\Soplados\Formulas::class)
            ->set('search_product', 'Envase')
            ->assertSet('product_results', function($results) {
                return collect($results)->pluck('name')->contains('Envase 3L Primera') 
                    && !collect($results)->pluck('name')->contains('Tapa Rosca');
            });

        // Search ingredients - should return 'Tapa Rosca' (since it IS raw material)
        // and NOT 'Envase 3L Primera' (since it is NOT raw material)
        Livewire::test(\App\Livewire\Soplados\Formulas::class)
            ->set('search_ingredient', 'Tapa')
            ->assertSet('ingredient_results', function($results) {
                return collect($results)->pluck('name')->contains('Tapa Rosca')
                    && !collect($results)->pluck('name')->contains('Envase 3L Primera');
            });
    }
}
