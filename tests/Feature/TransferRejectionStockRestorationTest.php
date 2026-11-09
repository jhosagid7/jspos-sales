<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\ProductWarehouse;
use App\Models\Transfer;
use App\Models\TransferDetail;
use App\Models\Configuration;
use App\Livewire\Transfers;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class TransferRejectionStockRestorationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $originWarehouse;
    protected $destWarehouse;
    protected $category;
    protected $supplier;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Admin']);
        $this->user = User::factory()->create();
        $this->user->assignRole('Admin');
        $this->actingAs($this->user);

        $this->originWarehouse = Warehouse::create([
            'name' => 'DEPOSITO ORIGEN',
            'is_active' => true,
        ]);

        $this->destWarehouse = Warehouse::create([
            'name' => 'DEPOSITO DESTINO',
            'is_active' => true,
        ]);

        $sopladosWarehouse = Warehouse::create([
            'name' => 'PLANTA SOPLADOS TEST',
            'is_active' => true,
        ]);

        Configuration::create([
            'business_name' => 'Test Business',
            'default_warehouse_id' => $this->originWarehouse->id,
            'soplados_warehouse_id' => $sopladosWarehouse->id,
        ]);

        $this->category = Category::create(['name' => 'General']);
        $this->supplier = Supplier::create([
            'name' => 'Proveedor Test',
            'taxpayer_id' => 'J-12345678-9',
        ]);

        $this->product = Product::create([
            'name' => 'PRODUCTO TEST',
            'sku' => 'TEST-REJECT-001',
            'cost' => 10.0,
            'price' => 20.0,
            'stock_qty' => 100.0,
            'low_stock' => 5,
            'manage_stock' => true,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'available',
        ]);

        ProductWarehouse::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->originWarehouse->id,
            'stock_qty' => 100.0,
        ]);

        ProductWarehouse::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->destWarehouse->id,
            'stock_qty' => 0.0,
        ]);
    }

    /** @test */
    public function rejecting_a_dispatched_transfer_restores_stock_to_origin_warehouse()
    {
        // 1. Create a transfer of 30 units
        $transfer = Transfer::create([
            'from_warehouse_id' => $this->originWarehouse->id,
            'to_warehouse_id' => $this->destWarehouse->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
            'note' => 'TEST TRANSFER',
        ]);

        $detail = TransferDetail::create([
            'transfer_id' => $transfer->id,
            'product_id' => $this->product->id,
            'quantity' => 30.0,
        ]);

        // 2. Dispatch transfer from web
        Livewire::test(Transfers::class)
            ->call('dispatchTransferFromWeb', $transfer->id);

        $transfer->refresh();
        $this->assertEquals('dispatched', $transfer->status);

        // Check stock was deducted from origin: 100 - 30 = 70
        $originPw = ProductWarehouse::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->originWarehouse->id)
            ->first();
        $this->assertEquals(70.0, (float)$originPw->stock_qty);

        // 3. Reject transfer
        Livewire::test(Transfers::class)
            ->call('rejectTransfer', $transfer->id);

        $transfer->refresh();
        $detail->refresh();

        // Assert status and details
        $this->assertEquals('rejected', $transfer->status);
        $this->assertEquals(0.0, (float)$detail->received_quantity);
        $this->assertEquals(30.0, (float)$detail->rejected_quantity);

        // Assert stock returned to origin warehouse: 70 + 30 = 100
        $originPw->refresh();
        $this->assertEquals(100.0, (float)$originPw->stock_qty, 'Origin warehouse stock must be fully restored upon rejection');

        // Destination stock should remain 0
        $destPw = ProductWarehouse::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->destWarehouse->id)
            ->first();
        $this->assertEquals(0.0, (float)$destPw->stock_qty);
    }

    /** @test */
    public function finalizing_a_transfer_with_rejections_from_non_soplados_origin_restores_rejected_stock()
    {
        // 1. Create a transfer of 40 units
        $transfer = Transfer::create([
            'from_warehouse_id' => $this->originWarehouse->id,
            'to_warehouse_id' => $this->destWarehouse->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
            'note' => 'PARTIAL TRANSFER TEST',
        ]);

        $detail = TransferDetail::create([
            'transfer_id' => $transfer->id,
            'product_id' => $this->product->id,
            'quantity' => 40.0,
        ]);

        // Dispatch
        Livewire::test(Transfers::class)
            ->call('dispatchTransferFromWeb', $transfer->id);

        // Origin stock is 100 - 40 = 60
        $originPw = ProductWarehouse::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->originWarehouse->id)
            ->first();
        $this->assertEquals(60.0, (float)$originPw->stock_qty);

        // 2. Receive 30 units, reject 10 units
        Livewire::test(Transfers::class)
            ->set('receiving_transfer_id', $transfer->id)
            ->set('receiving_details', [
                $detail->id => [
                    'product_name' => $this->product->name,
                    'requested' => 40.0,
                    'received' => 30.0,
                ],
            ])
            ->set('rejection_reason', 'Faltaron 10 unidades')
            ->call('finalizeTransfer');

        $transfer->refresh();
        $detail->refresh();
        $this->assertEquals('completed_partial', $transfer->status);
        $this->assertEquals(30.0, (float)$detail->received_quantity);
        $this->assertEquals(10.0, (float)$detail->rejected_quantity);

        // Destination receives 30
        $destPw = ProductWarehouse::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->destWarehouse->id)
            ->first();
        $this->assertEquals(30.0, (float)$destPw->stock_qty);

        // Origin receives rejected 10 back: 60 + 10 = 70
        $originPw->refresh();
        $this->assertEquals(70.0, (float)$originPw->stock_qty);
    }
}
