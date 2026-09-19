<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\ProductItem;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Configuration;
use App\Models\ProductionFormula;
use App\Models\Shift;
use App\Models\Transfer;
use App\Models\TransferDetail;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Livewire\Transfers;
use App\Livewire\Inventory;
use App\Livewire\SalesReport;
use App\Livewire\ProductItemsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class StockMovementConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $defaultWarehouse;
    protected $factoryWarehouse;
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

        // Create Warehouses
        $this->defaultWarehouse = Warehouse::create([
            'name' => 'Tienda Principal',
            'address' => 'Local Comercial',
            'is_active' => true,
        ]);

        $this->factoryWarehouse = Warehouse::create([
            'name' => 'Planta Soplados',
            'address' => 'Zona Industrial',
            'is_active' => true,
        ]);

        // Setup Configuration
        Configuration::create([
            'default_warehouse_id' => $this->defaultWarehouse->id,
            'soplados_warehouse_id' => $this->factoryWarehouse->id,
            'production_materials_warehouse_id' => $this->factoryWarehouse->id,
            'business_name' => 'JSPOS Test',
        ]);

        // Permissions & User
        \Spatie\Permission\Models\Permission::findOrCreate('soplados.manager', 'web');
        \Spatie\Permission\Models\Permission::findOrCreate('soplados.operator', 'web');

        $this->user = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'warehouse_id' => $this->defaultWarehouse->id,
        ]);
        $this->user->givePermissionTo('soplados.manager');
        $this->user->givePermissionTo('soplados.operator');

        $this->actingAs($this->user);

        $this->category = Category::create(['name' => 'General']);
        $this->supplier = Supplier::create([
            'name' => 'Proveedor Principal',
            'taxpayer_id' => 'J-00000000-0',
            'address' => 'Calle 1',
            'phone' => '1234567',
        ]);
    }

    public function test_transfers_from_default_to_secondary_warehouse_conserves_global_stock()
    {
        // 1. Create Product with 100 in Default Warehouse, 0 in Factory Warehouse, Global = 100
        $product = Product::create([
            'name' => 'Envase 1L',
            'sku' => 'ENV-1L',
            'cost' => 0.50,
            'price' => 1.00,
            'stock_qty' => 100,
            'manage_stock' => true,
            'low_stock' => 0,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'available',
        ]);

        ProductWarehouse::create([
            'product_id' => $product->id,
            'warehouse_id' => $this->defaultWarehouse->id,
            'stock_qty' => 100,
        ]);

        ProductWarehouse::create([
            'product_id' => $product->id,
            'warehouse_id' => $this->factoryWarehouse->id,
            'stock_qty' => 0,
        ]);

        // 2. Transfer 30 units from Default to Factory
        $transfer = Transfer::create([
            'from_warehouse_id' => $this->defaultWarehouse->id,
            'to_warehouse_id' => $this->factoryWarehouse->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
            'note' => 'Test transfer default to secondary',
        ]);

        TransferDetail::create([
            'transfer_id' => $transfer->id,
            'product_id' => $product->id,
            'quantity' => 30,
        ]);

        // Dispatch transfer via Livewire component
        $component = Livewire::test(Transfers::class);
        $component->call('dispatchTransferFromWeb', $transfer->id);

        $this->assertEquals(70, ProductWarehouse::where('product_id', $product->id)->where('warehouse_id', $this->defaultWarehouse->id)->value('stock_qty'));

        // Receive / Approve transfer
        $component->call('approveTransfer', $transfer->id);

        $this->assertEquals(70, ProductWarehouse::where('product_id', $product->id)->where('warehouse_id', $this->defaultWarehouse->id)->value('stock_qty'));
        $this->assertEquals(30, ProductWarehouse::where('product_id', $product->id)->where('warehouse_id', $this->factoryWarehouse->id)->value('stock_qty'));

        // Global stock MUST be preserved at 100 (70 + 30)
        $product->refresh();
        $this->assertEquals(100, $product->stock_qty, 'Global stock was corrupted during transfer from default to secondary warehouse');
    }

    public function test_transfers_from_secondary_to_default_warehouse_conserves_global_stock()
    {
        // 1. Create Product with 50 in Factory, 50 in Store, Global = 100
        $product = Product::create([
            'name' => 'Tapa Rosca',
            'sku' => 'TAP-01',
            'cost' => 0.10,
            'price' => 0.25,
            'stock_qty' => 100,
            'manage_stock' => true,
            'low_stock' => 0,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'available',
        ]);

        ProductWarehouse::create([
            'product_id' => $product->id,
            'warehouse_id' => $this->factoryWarehouse->id,
            'stock_qty' => 50,
        ]);

        ProductWarehouse::create([
            'product_id' => $product->id,
            'warehouse_id' => $this->defaultWarehouse->id,
            'stock_qty' => 50,
        ]);

        // 2. Transfer 20 units from Factory to Default
        $transfer = Transfer::create([
            'from_warehouse_id' => $this->factoryWarehouse->id,
            'to_warehouse_id' => $this->defaultWarehouse->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
            'note' => 'Test transfer secondary to default',
        ]);

        TransferDetail::create([
            'transfer_id' => $transfer->id,
            'product_id' => $product->id,
            'quantity' => 20,
        ]);

        $component = Livewire::test(Transfers::class);
        $component->call('dispatchTransferFromWeb', $transfer->id);
        $component->call('approveTransfer', $transfer->id);

        $this->assertEquals(30, ProductWarehouse::where('product_id', $product->id)->where('warehouse_id', $this->factoryWarehouse->id)->value('stock_qty'));
        $this->assertEquals(70, ProductWarehouse::where('product_id', $product->id)->where('warehouse_id', $this->defaultWarehouse->id)->value('stock_qty'));

        $product->refresh();
        $this->assertEquals(100, $product->stock_qty, 'Global stock was corrupted during transfer from secondary to default warehouse');
    }

    public function test_factory_production_increments_warehouse_and_global_stock()
    {
        // 1. Raw material
        $rawMaterial = Product::create([
            'name' => 'Polietileno HD',
            'sku' => 'MAT-PE-HD',
            'cost' => 1.20,
            'price' => 1.50,
            'stock_qty' => 500,
            'is_raw_material' => true,
            'manage_stock' => true,
            'low_stock' => 0,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'available',
        ]);

        ProductWarehouse::create([
            'product_id' => $rawMaterial->id,
            'warehouse_id' => $this->factoryWarehouse->id,
            'stock_qty' => 500,
        ]);

        // 2. Finished Product
        $finishedProduct = Product::create([
            'name' => 'Galon Industrial',
            'sku' => 'GAL-IND',
            'cost' => 0.80,
            'price' => 2.00,
            'stock_qty' => 0,
            'is_raw_material' => false,
            'manage_stock' => true,
            'low_stock' => 0,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'available',
        ]);

        // Add 'soplados' tag
        $tag = \App\Models\Tag::firstOrCreate(['name' => 'soplados']);
        $finishedProduct->tags()->attach($tag->id);

        // Formula: 2 kg of PE per galon
        ProductionFormula::create([
            'product_id' => $finishedProduct->id,
            'ingredient_id' => $rawMaterial->id,
            'quantity' => 2.00,
        ]);

        $shift = Shift::create([
            'user_id' => $this->user->id,
            'warehouse_id' => $this->factoryWarehouse->id,
            'type' => 'day',
            'start_time' => now(),
            'status' => 'open',
        ]);

        // 3. Post Production of 50 units
        $response = $this->postJson('/api/soplados/production', [
            'shift_id' => $shift->id,
            'warehouse_id' => $this->factoryWarehouse->id,
            'notes' => 'Lote de prueba',
            'outputs' => [
                [
                    'product_id' => $finishedProduct->id,
                    'quantity' => 50,
                    'quality' => '1st',
                ]
            ],
        ]);

        $response->assertStatus(200);

        // Verify Finished Product Stock in Factory & Global
        $finishedProduct->refresh();
        $this->assertEquals(50, $finishedProduct->stock_qty, 'Global stock for finished goods did not increment after factory production');
        $this->assertEquals(50, ProductWarehouse::where('product_id', $finishedProduct->id)->where('warehouse_id', $this->factoryWarehouse->id)->value('stock_qty'));

        // Verify Raw Material Stock Deducted in Factory & Global (50 * 2 = 100 deducted)
        $rawMaterial->refresh();
        $this->assertEquals(400, $rawMaterial->stock_qty, 'Global raw material stock was not deducted');
        $this->assertEquals(400, ProductWarehouse::where('product_id', $rawMaterial->id)->where('warehouse_id', $this->factoryWarehouse->id)->value('stock_qty'));
    }

    public function test_inventory_adjust_syncs_product_warehouse_and_global_stock()
    {
        $product = Product::create([
            'name' => 'Producto Ajuste',
            'sku' => 'ADJ-001',
            'cost' => 10.00,
            'price' => 15.00,
            'stock_qty' => 20,
            'manage_stock' => true,
            'low_stock' => 0,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'available',
        ]);

        ProductWarehouse::create([
            'product_id' => $product->id,
            'warehouse_id' => $this->defaultWarehouse->id,
            'stock_qty' => 20,
        ]);

        $component = Livewire::test(Inventory::class);

        // Action 2: Sumar 5 unidades
        $component->call('Ajustar', $product->id, 5, 2);
        $product->refresh();
        $this->assertEquals(25, $product->stock_qty);
        $this->assertEquals(25, ProductWarehouse::where('product_id', $product->id)->where('warehouse_id', $this->defaultWarehouse->id)->value('stock_qty'));

        // Action 1: Restar 10 unidades
        $component->call('Ajustar', $product->id, 10, 1);
        $product->refresh();
        $this->assertEquals(15, $product->stock_qty);
        $this->assertEquals(15, ProductWarehouse::where('product_id', $product->id)->where('warehouse_id', $this->defaultWarehouse->id)->value('stock_qty'));

        // Action 3: Ajustar a 50 unidades
        $component->call('Ajustar', $product->id, 50, 3);
        $product->refresh();
        $this->assertEquals(50, $product->stock_qty);
        $this->assertEquals(50, ProductWarehouse::where('product_id', $product->id)->where('warehouse_id', $this->defaultWarehouse->id)->value('stock_qty'));
    }

    public function test_sale_cancel_restores_product_item_and_stock()
    {
        // 1. Create Product and ProductItem
        $product = Product::create([
            'name' => 'Bobina Plastica 50kg',
            'sku' => 'BOB-50',
            'cost' => 25.00,
            'price' => 50.00,
            'stock_qty' => 50,
            'manage_stock' => true,
            'low_stock' => 0,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'available',
        ]);

        $productWarehouse = ProductWarehouse::create([
            'product_id' => $product->id,
            'warehouse_id' => $this->defaultWarehouse->id,
            'stock_qty' => 50,
        ]);

        $item = ProductItem::create([
            'product_id' => $product->id,
            'warehouse_id' => $this->defaultWarehouse->id,
            'quantity' => 50,
            'original_quantity' => 50,
            'status' => 'sold', // Mark as sold in order
            'batch' => 'BATCH-99',
        ]);

        // Deduct initial sale stock
        $product->update(['stock_qty' => 0]);
        $productWarehouse->update(['stock_qty' => 0]);

        $customer = \App\Models\Customer::create([
            'name' => 'Cliente Test',
            'taxpayer_id' => 'V-12345678-0',
            'phone' => '04141234567',
            'address' => 'Test Address',
        ]);

        $sale = \App\Models\Sale::create([
            'user_id' => $this->user->id,
            'customer_id' => $customer->id,
            'warehouse_id' => $this->defaultWarehouse->id,
            'total' => 50.00,
            'type' => 'Cash',
            'status' => 'paid',
            'items' => 1,
            'cash' => 50.00,
            'change' => 0,
        ]);

        $saleDetail = \App\Models\SaleDetail::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 50,
            'regular_price' => 50.00,
            'sale_price' => 50.00,
            'discount' => 0,
            'warehouse_id' => $this->defaultWarehouse->id,
            'metadata' => json_encode(['product_item_id' => $item->id]),
        ]);

        // Cancel the sale via SalesReport
        $component = Livewire::test(SalesReport::class);
        $component->call('executeSaleDeletion', $sale, 'Error en despacho', $this->user->id);

        // Verify ProductItem is restored to available
        $item->refresh();
        $this->assertEquals('available', $item->status, 'ProductItem status was not restored to available on sale cancellation');

        // Verify stock is restored in warehouse and global
        $product->refresh();
        $productWarehouse->refresh();
        $this->assertEquals(50, $product->stock_qty);
        $this->assertEquals(50, $productWarehouse->stock_qty);

        // Verify that running ProductItemsManager::updateMasterStock calculates the exact available sum
        $manager = Livewire::test(ProductItemsManager::class, ['productId' => $product->id]);
        $manager->call('updateMasterStock');

        $product->refresh();
        $this->assertEquals(50, $product->stock_qty, 'updateMasterStock lost restored stock after cancelSale');
    }
}
