<?php

namespace Tests\Feature;

use App\Models\BagShift;
use App\Models\BagProduction;
use App\Models\Category;
use App\Models\Product;
use App\Models\Production;
use App\Models\ProductionDetail;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BagFactoryLiftingApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $warehouseStaff;
    protected User $supervisor;
    protected User $operator;
    protected Warehouse $warehouse;
    protected Supplier $supplier;
    protected Product $product1;
    protected Product $product2;
    protected BagShift $shift;

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

        $this->warehouse = Warehouse::create([
            'name'      => 'AlmacÃƒÂ©n Principal JSPOS',
            'is_active' => 1,
        ]);

        $this->supplier = Supplier::create([
            'name'        => 'M&F Steel SA',
            'taxpayer_id' => 'J-12345678-0',
            'address'     => 'DirecciÃƒÂ³n FÃƒÂ¡brica',
            'phone'       => '12345678',
        ]);

        $category = Category::create(['name' => 'BOLSAS']);

        $this->product1 = Product::create([
            'name'        => 'Bolsa Negra 50x70',
            'sku'         => 'BN-5070',
            'category_id' => $category->id,
            'supplier_id' => $this->supplier->id,
            'cost'        => 2.0,
            'price'       => 3.5,
            'stock_qty'   => 0,
            'low_stock'   => 0,
        ]);

        $this->product2 = Product::create([
            'name'        => 'Bolsa Transparente 30x40',
            'sku'         => 'BT-3040',
            'category_id' => $category->id,
            'supplier_id' => $this->supplier->id,
            'cost'        => 1.5,
            'price'       => 2.8,
            'stock_qty'   => 0,
            'low_stock'   => 0,
        ]);

        $this->warehouseStaff = User::create([
            'name'         => 'Mario AlmacÃƒÂ©n',
            'email'        => 'mario.almacen@jspos.test',
            'password'     => bcrypt('password123'),
            'warehouse_id' => $this->warehouse->id,
        ]);

        $this->supervisor = User::create([
            'name'         => 'Carlos Supervisor',
            'email'        => 'carlos.supervisor@bolsas.test',
            'password'     => bcrypt('password123'),
            'warehouse_id' => $this->warehouse->id,
        ]);

        $this->operator = User::create([
            'name'         => 'Pedro Operario',
            'email'        => 'pedro.operario@bolsas.test',
            'password'     => bcrypt('password123'),
            'warehouse_id' => $this->warehouse->id,
        ]);

        $this->shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'shift_type' => 'diurno',
            'start_time' => now()->subHours(4),
            'status'     => 'open',
        ]);
    }

    public function test_warehouse_staff_can_get_pending_lifting_bultos(): void
    {
        // 1 Approved (Ready for lifting)
        BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product1->id,
            'quantity'     => 3,
            'weight'       => 60.0000,
            'recorded_at'  => now(),
            'status'       => 'approved',
            'reviewed_by'  => $this->supervisor->id,
            'reviewed_at'  => now(),
            'qr_code'      => 'PKG-READY-001',
        ]);

        // 1 Pending review (Should NOT be available for lifting)
        BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product2->id,
            'quantity'     => 1,
            'weight'       => 20.0000,
            'recorded_at'  => now(),
            'status'       => 'pending_review',
            'qr_code'      => 'PKG-PEND-002',
        ]);

        $response = $this->actingAs($this->warehouseStaff)
            ->getJson('/api/bag-factory/lifting/pending');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'totals'  => [
                    'count'          => 1,
                    'total_packages' => 3.0,
                    'total_weight'   => 60.0,
                ],
            ])
            ->assertJsonFragment(['qr_code' => 'PKG-READY-001'])
            ->assertJsonMissing(['qr_code' => 'PKG-PEND-002']);
    }

    public function test_warehouse_staff_can_scan_qr_code(): void
    {
        $prod = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product1->id,
            'quantity'     => 2,
            'weight'       => 45.0000,
            'recorded_at'  => now(),
            'status'       => 'approved',
            'qr_code'      => 'PKG-SCAN-8899',
        ]);

        $response = $this->actingAs($this->warehouseStaff)
            ->getJson('/api/bag-factory/lifting/scan/PKG-SCAN-8899');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'id'           => $prod->id,
                    'qr_code'      => 'PKG-SCAN-8899',
                    'product_name' => 'Bolsa Negra 50x70',
                    'weight'       => 45.0,
                    'is_ready'     => true,
                ],
            ]);
    }

    public function test_warehouse_staff_can_receive_bultos_and_generate_jspos_production(): void
    {
        $p1 = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product1->id,
            'quantity'     => 2,
            'weight'       => 40.0000,
            'recorded_at'  => now(),
            'status'       => 'approved',
            'qr_code'      => 'PKG-LIFT-111',
        ]);

        $p2 = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product2->id,
            'quantity'     => 3,
            'weight'       => 50.0000,
            'recorded_at'  => now(),
            'status'       => 'approved',
            'qr_code'      => 'PKG-LIFT-222',
        ]);

        $payload = [
            'production_ids' => [$p1->id, $p2->id],
            'warehouse_id'   => $this->warehouse->id,
            'notes'          => 'RecepciÃƒÂ³n oficial de bultos desde FÃƒÂ¡brica JSBolsas',
        ];

        $response = $this->actingAs($this->warehouseStaff)
            ->postJson('/api/bag-factory/lifting/receive', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success'        => true,
                'received_count' => 2,
            ]);

        // 1. Verify JSPOS Production header & details were created
        $this->assertDatabaseHas('productions', [
            'user_id' => $this->warehouseStaff->id,
            'status'  => 'pending',
            'note'    => 'RecepciÃƒÂ³n oficial de bultos desde FÃƒÂ¡brica JSBolsas',
        ]);

        $this->assertDatabaseCount('production_details', 2);

        // 2. Verify bag_productions were updated to 'lifted'
        $p1->refresh();
        $p2->refresh();
        $this->assertEquals('lifted', $p1->status);
        $this->assertEquals('lifted', $p2->status);
        $this->assertEquals($this->warehouseStaff->id, $p1->lifted_by);
        $this->assertNotNull($p1->lifted_at);
        $this->assertNotNull($p1->jspos_production_id);
    }
}