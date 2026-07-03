<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Shift;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Category;
use App\Models\Configuration;
use App\Models\ProductionFormula;
use App\Models\ProductionLog;
use App\Models\ProductionOutput;
use App\Models\SopladosInventory;
use App\Models\SopladosInventoryDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SopladosProductionAndInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected $supervisor;
    protected $operator;
    protected $warehouse;
    protected $category;
    protected $product1st;
    protected $product2nd;
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

        // Create warehouse
        $this->warehouse = Warehouse::create([
            'name' => 'Planta Soplados',
            'address' => 'Planta Industrial',
            'is_active' => true,
        ]);

        // Create permissions in DB
        \Spatie\Permission\Models\Permission::findOrCreate('soplados.manager', 'web');
        \Spatie\Permission\Models\Permission::findOrCreate('soplados.operator', 'web');

        // Create Users with appropriate permissions
        $this->supervisor = User::factory()->create([
            'name' => 'Supervisor Soplados',
            'email' => 'supervisor@example.com',
            'warehouse_id' => $this->warehouse->id,
        ]);
        $this->supervisor->givePermissionTo('soplados.manager');

        $this->operator = User::factory()->create([
            'name' => 'Operador Soplados',
            'email' => 'operator@example.com',
            'warehouse_id' => $this->warehouse->id,
        ]);
        $this->operator->givePermissionTo('soplados.operator');

        $this->actingAs($this->supervisor);

        // Create Category
        $this->category = Category::create([
            'name' => 'Test Category',
        ]);

        // Create Supplier
        $supplier = \App\Models\Supplier::create([
            'name' => 'Supplier Test',
            'taxpayer_id' => 'J-11111111-1',
            'address' => 'Supplier Address',
            'phone' => '12345678',
        ]);

        // Create 2nd quality product first
        $this->product2nd = Product::create([
            'sku' => 'GALON-2DA',
            'name' => 'Galon 3.785L 2da Calidad',
            'cost' => 0.40,
            'price' => 1.00,
            'stock_qty' => 0,
            'low_stock' => 0,
            'manage_stock' => true,
            'category_id' => $this->category->id,
            'supplier_id' => $supplier->id,
            'status' => 'available',
        ]);

        // Create 1st quality product linking to 2nd quality product
        $this->product1st = Product::create([
            'sku' => 'GALON-1RA',
            'name' => 'Galon 3.785L 1ra Calidad',
            'cost' => 0.50,
            'price' => 1.50,
            'stock_qty' => 0,
            'low_stock' => 0,
            'manage_stock' => true,
            'category_id' => $this->category->id,
            'supplier_id' => $supplier->id,
            'status' => 'available',
            'production_target_id' => $this->product2nd->id,
        ]);

        $this->rawMaterial = Product::create([
            'sku' => 'MAT-RESINA',
            'name' => 'Resina PE',
            'cost' => 1.00,
            'price' => 0.00,
            'stock_qty' => 1000,
            'low_stock' => 0,
            'manage_stock' => true,
            'is_raw_material' => true,
            'category_id' => $this->category->id,
            'supplier_id' => $supplier->id,
            'status' => 'available',
        ]);

        // Add 'soplados' tag to products
        $tag = \App\Models\Tag::firstOrCreate(['name' => 'soplados']);
        $this->product1st->tags()->attach($tag);
        $this->product2nd->tags()->attach($tag);
        $this->rawMaterial->tags()->attach($tag);

        // Associate stock in warehouses
        \App\Models\ProductWarehouse::create([
            'product_id' => $this->product1st->id,
            'warehouse_id' => $this->warehouse->id,
            'stock_qty' => 0.00
        ]);
        \App\Models\ProductWarehouse::create([
            'product_id' => $this->product2nd->id,
            'warehouse_id' => $this->warehouse->id,
            'stock_qty' => 0.00
        ]);
        \App\Models\ProductWarehouse::create([
            'product_id' => $this->rawMaterial->id,
            'warehouse_id' => $this->warehouse->id,
            'stock_qty' => 500.00
        ]);

        // Create production formulas
        ProductionFormula::create([
            'product_id' => $this->product1st->id,
            'ingredient_id' => $this->rawMaterial->id,
            'quantity' => 0.150, // 150g per bottle
        ]);

        Configuration::create([
            'business_name' => 'Test Factory',
            'decimals' => 2,
            'vat' => 16,
            'soplados_warehouse_id' => $this->warehouse->id,
        ]);
    }

    public function test_production_quality_separates_stock_updates_correctly()
    {
        $this->actingAs($this->supervisor);

        // Create Shift
        $shift = Shift::create([
            'type' => 'day',
            'start_time' => now(),
            'status' => 'open',
            'user_id' => $this->supervisor->id,
            'warehouse_id' => $this->warehouse->id,
        ]);

        // Register 1st quality production
        $response1 = $this->postJson('/api/soplados/production', [
            'shift_id' => $shift->id,
            'warehouse_id' => $this->warehouse->id,
            'outputs' => [
                [
                    'product_id' => $this->product1st->id,
                    'quantity' => 100,
                    'quality' => '1st',
                ]
            ]
        ]);
        $response1->assertStatus(200);

        // Verify only 1st quality stock increased
        $this->assertEquals(100, \App\Models\ProductWarehouse::where('product_id', $this->product1st->id)->where('warehouse_id', $this->warehouse->id)->value('stock_qty'));
        $this->assertEquals(0, \App\Models\ProductWarehouse::where('product_id', $this->product2nd->id)->where('warehouse_id', $this->warehouse->id)->value('stock_qty'));

        // Register 2nd quality production
        $response2 = $this->postJson('/api/soplados/production', [
            'shift_id' => $shift->id,
            'warehouse_id' => $this->warehouse->id,
            'outputs' => [
                [
                    'product_id' => $this->product1st->id,
                    'quantity' => 50,
                    'quality' => '2nd',
                ]
            ]
        ]);
        $response2->assertStatus(200);

        // Verify only 2nd quality stock increased
        $this->assertEquals(100, \App\Models\ProductWarehouse::where('product_id', $this->product1st->id)->where('warehouse_id', $this->warehouse->id)->value('stock_qty'));
        $this->assertEquals(50, \App\Models\ProductWarehouse::where('product_id', $this->product2nd->id)->where('warehouse_id', $this->warehouse->id)->value('stock_qty'));
    }

    public function test_expected_merma_is_calculated_and_stored_correctly()
    {
        $this->actingAs($this->supervisor);

        $shift = Shift::create([
            'type' => 'day',
            'start_time' => now()->subHours(2),
            'status' => 'open',
            'user_id' => $this->supervisor->id,
            'warehouse_id' => $this->warehouse->id,
        ]);

        // Register production with damaged output (merma)
        $this->postJson('/api/soplados/production', [
            'shift_id' => $shift->id,
            'outputs' => [
                [
                    'product_id' => $this->product1st->id,
                    'quantity' => 12.5,
                    'quality' => 'damaged', // Merma
                ]
            ]
        ])->assertStatus(200);
 
        // 1. Check productsForCount endpoint returns the expected merma
        $response = $this->getJson('/api/soplados/inventory/products');
        $response->assertStatus(200);
        
        $products = $response->json('products');
        $finished = collect($products)->where('id', $this->product1st->id)->first();
        
        $this->assertNotNull($finished);
        $this->assertEquals(12.5, $finished['system_stock_merma']);
 
        // 2. Submit initial physical count
        $storeResponse = $this->postJson('/api/soplados/inventory', [
            'notes' => 'Test inventory',
            'products' => [
                [
                    'id' => $this->product1st->id,
                    'type' => 'finished_product',
                    'counted_primera' => 100,
                    'counted_segunda' => 50,
                    'counted_merma' => 10, // Physical count is 10 (expected 12.5, diff = -2.5)
                ],
                [
                    'id' => $this->rawMaterial->id,
                    'type' => 'raw_material',
                    'counted_primera' => 450,
                ]
            ]
        ]);
        $storeResponse->assertStatus(200);
 
        $inventoryId = $storeResponse->json('inventory.id');
 
        // Check stored details has correct merma fields
        $detail = SopladosInventoryDetail::where('soplados_inventory_id', $inventoryId)
            ->where('product_id', $this->product1st->id)
            ->first();
 
        $this->assertNotNull($detail);
        $this->assertEquals(12.5, $detail->system_stock_merma);
        $this->assertEquals(10, $detail->counted_merma);
        $this->assertEquals(-2.5, $detail->difference_merma);
 
        // 3. Operator accepts count, completing the inventory
        $this->actingAs($this->operator);
        $acceptResponse = $this->postJson("/api/soplados/inventory/{$inventoryId}/accept", [
            'operator_notes' => 'All matches'
        ]);
        $acceptResponse->assertStatus(200);
 
        // 4. Register new production with merma
        $this->actingAs($this->supervisor);
        
        // Travel 5 seconds into the future to ensure new production is created_at > accepted_at
        \Illuminate\Support\Carbon::setTestNow(now()->addSeconds(5));

        $this->postJson('/api/soplados/production', [
            'shift_id' => $shift->id,
            'outputs' => [
                [
                    'product_id' => $this->product1st->id,
                    'quantity' => 7.0,
                    'quality' => 'damaged',
                ]
            ]
        ])->assertStatus(200);
 
        // Verify that productsForCount now only counts the merma created AFTER the accepted inventory (should be 7.0, not 19.5)
        $responseNew = $this->getJson('/api/soplados/inventory/products');
        $responseNew->assertStatus(200);
        
        $productsNew = $responseNew->json('products');
        $finishedNew = collect($productsNew)->where('id', $this->product1st->id)->first();
        
        $this->assertEquals(7.0, $finishedNew['system_stock_merma']);

        \Illuminate\Support\Carbon::setTestNow();
    }
}
