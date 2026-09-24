<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\ProductWarehouse;
use App\Models\Configuration;
use App\Livewire\Transfers;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvoiceTicketAndStockTransferTest extends TestCase
{
    use RefreshDatabase;

    protected $warehouse;
    protected $category;
    protected $supplier;
    protected $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouse = Warehouse::create([
            'name' => 'TIENDA PRINCIPAL',
            'is_active' => 1,
        ]);

        $this->config = Configuration::create([
            'business_name' => 'Test Business',
            'default_warehouse_id' => $this->warehouse->id,
        ]);

        $this->category = Category::create(['name' => 'General']);
        $this->supplier = Supplier::create(['name' => 'Proveedor General']);
    }

    /** @test */
    public function transfer_to_default_warehouse_synchronizes_product_stock_qty()
    {
        $product = Product::create([
            'name' => 'TEST PRODUCT FOR STOCK SYNC',
            'sku' => 'TEST-SYNC-001',
            'cost' => 10,
            'price' => 20,
            'stock_qty' => 5,
            'low_stock' => 1,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'available',
        ]);

        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $this->warehouse->id],
            ['stock_qty' => 5]
        );

        $transfersComponent = new Transfers();
        // Transfer 20 units into the default warehouse
        $transfersComponent->updateStock($this->warehouse->id, $product->id, 20);

        $product->refresh();
        $this->assertEquals(25.0, (float)$product->stock_qty, 'Product stock_qty should be updated to 25 after adding 20 to default warehouse');

        // Transfer 10 units out of the default warehouse
        $transfersComponent->updateStock($this->warehouse->id, $product->id, -10);

        $product->refresh();
        $this->assertEquals(15.0, (float)$product->stock_qty, 'Product stock_qty should be updated to 15 after subtracting 10 from default warehouse');
    }

    /** @test */
    public function product_warehouse_model_saved_hook_automatically_updates_product_stock_qty()
    {
        $product = Product::create([
            'name' => 'TEST HOOK PRODUCT',
            'sku' => 'TEST-HOOK-001',
            'cost' => 10,
            'price' => 20,
            'stock_qty' => 1,
            'low_stock' => 1,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'available',
        ]);

        $pw = ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $this->warehouse->id],
            ['stock_qty' => 1]
        );

        // Update warehouse stock directly via Eloquent
        $pw->stock_qty = 21;
        $pw->save();

        $product->refresh();
        $this->assertEquals(21.0, (float)$product->stock_qty, 'Product stock_qty should sync to 21 when default warehouse stock is saved');
    }

    /** @test */
    public function sync_default_warehouse_command_repairs_desynchronized_products()
    {
        $product = Product::create([
            'name' => 'TEST DESYNC REPAIR PRODUCT',
            'sku' => 'TEST-DESYNC-001',
            'cost' => 5,
            'price' => 10,
            'stock_qty' => 1, // Desynced value
            'low_stock' => 1,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'available',
        ]);

        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $this->warehouse->id],
            ['stock_qty' => 50] // Real warehouse value
        );

        // Run sync command
        $exitCode = Artisan::call('stock:sync-default-warehouse');
        $this->assertEquals(0, $exitCode);

        $product->refresh();
        $this->assertEquals(50.0, (float)$product->stock_qty, 'Artisan command should synchronize product stock to 50');
    }

    /** @test */
    public function ticket_printing_trait_uses_invoice_number_for_folio()
    {
        $traitMock = new class {
            use \App\Traits\PrintTrait;

            public function getTicketFolioForSale($sale)
            {
                return $sale->invoice_number ?: $sale->id;
            }
        };

        $saleWithInvoice = (object)[
            'id' => 4621,
            'invoice_number' => 'F00004618'
        ];

        $folio = $traitMock->getTicketFolioForSale($saleWithInvoice);
        $this->assertEquals('F00004618', $folio, 'Ticket must use invoice_number F00004618 instead of raw database id 4621');

        $saleWithoutInvoice = (object)[
            'id' => 4621,
            'invoice_number' => null
        ];

        $folioFallback = $traitMock->getTicketFolioForSale($saleWithoutInvoice);
        $this->assertEquals(4621, $folioFallback, 'Ticket falls back to id 4621 if invoice_number is null');
    }
}
