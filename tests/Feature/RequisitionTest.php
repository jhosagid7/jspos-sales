<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Livewire\Requisition;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RequisitionTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $category;
    protected $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['profile' => 'ADMIN']);

        $this->category = \App\Models\Category::create(['name' => 'Bolsas']);
        $this->supplier = Supplier::create([
            'name' => 'Proveedor de Bolsas',
            'taxpayer_id' => 'J88888888',
            'address' => 'Caracas',
            'phone' => '0212-0000000',
        ]);

        $this->p1 = Product::create([
            'name' => 'Bolsa 20x30',
            'sku' => 'B-2030',
            'stock_qty' => 5,
            'max_stock' => 10,
            'cost' => 1.50,
            'price' => 3.00,
            'price_usd' => 3.00,
            'show_in_sales' => true,
            'manage_stock' => true,
            'low_stock' => 0,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id
        ]);

        $this->p2 = Product::create([
            'name' => 'Bolsa 20x45',
            'sku' => 'B-2045',
            'stock_qty' => 2,
            'max_stock' => 10,
            'cost' => 2.00,
            'price' => 4.00,
            'price_usd' => 4.00,
            'show_in_sales' => true,
            'manage_stock' => true,
            'low_stock' => 0,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id
        ]);

        $this->p3 = Product::create([
            'name' => 'Bolsa 30x40',
            'sku' => 'B-3040',
            'stock_qty' => 0,
            'max_stock' => 10,
            'cost' => 3.00,
            'price' => 6.00,
            'price_usd' => 6.00,
            'show_in_sales' => true,
            'manage_stock' => true,
            'low_stock' => 0,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id
        ]);
    }

    public function test_requisition_can_sort_selected_items_and_create_order_in_that_order()
    {
        $this->actingAs($this->adminUser);

        // Select items in random order: p2, p1, p3
        $selected = [$this->p2->id, $this->p1->id, $this->p3->id];

        $component = Livewire::test(Requisition::class)
            ->set('selected', $selected);

        // Move p1 (index 1) UP to index 0 -> Order becomes: p1, p2, p3
        $component->call('moveProductUp', $this->p1->id);
        $this->assertEquals([$this->p1->id, $this->p2->id, $this->p3->id], $component->get('selected'));

        // Move p3 (index 2) UP to index 1 -> Order becomes: p1, p3, p2
        $component->call('moveProductUp', $this->p3->id);
        $this->assertEquals([$this->p1->id, $this->p3->id, $this->p2->id], $component->get('selected'));

        // Move p3 (index 1) DOWN to index 2 -> Order becomes: p1, p2, p3
        $component->call('moveProductDown', $this->p3->id);
        $this->assertEquals([$this->p1->id, $this->p2->id, $this->p3->id], $component->get('selected'));

        // Create the order
        $component->call('createOrder');

        // Check database purchase order
        $purchase = Purchase::first();
        $this->assertNotNull($purchase);

        // Assert that the Purchase Details are saved in the EXACT sorted order: p1, p2, p3
        $details = PurchaseDetail::where('purchase_id', $purchase->id)
            ->orderBy('id', 'asc')
            ->get();

        $this->assertCount(3, $details);
        $this->assertEquals($this->p1->id, $details[0]->product_id);
        $this->assertEquals($this->p2->id, $details[1]->product_id);
        $this->assertEquals($this->p3->id, $details[2]->product_id);
    }

    public function test_requisition_can_reorder_via_drag_and_drop()
    {
        $this->actingAs($this->adminUser);

        // Select items in order: p2, p1, p3
        $selected = [$this->p2->id, $this->p1->id, $this->p3->id];

        $component = Livewire::test(Requisition::class)
            ->set('selected', $selected);

        // Move index 0 (p2) to index 1 -> Order becomes: p1, p2, p3
        $component->call('reorderProducts', 0, 1);
        $this->assertEquals([$this->p1->id, $this->p2->id, $this->p3->id], $component->get('selected'));

        // Move index 2 (p3) to index 0 -> Order becomes: p3, p1, p2
        $component->call('reorderProducts', 2, 0);
        $this->assertEquals([$this->p3->id, $this->p1->id, $this->p2->id], $component->get('selected'));
    }

    public function test_pending_purchase_flow_does_not_alter_stock_until_finalized()
    {
        $this->actingAs($this->adminUser);

        // Seed Warehouse and Configuration (needed for Purchases component)
        $warehouse = \App\Models\Warehouse::create([
            'name' => 'TIENDA PRINCIPAL',
            'is_active' => 1,
        ]);

        \App\Models\Configuration::create([
            'business_name' => 'Steel Plastics Factory',
            'taxpayer_id' => 'V12345678',
            'address' => 'Factory Lane',
            'city' => 'Caracas',
            'phone' => '0212-0000000',
            'decimals' => 2,
            'vat' => 16,
            'printer_name' => 'PDF',
            'credit_days' => 15,
            'default_warehouse_id' => $warehouse->id,
            'bcv_rate' => 600.00,
            'binance_rate' => 700.00,
        ]);

        // 1. Initial stock is 5
        $initialStock = $this->p1->stock_qty;
        $this->assertEquals(5, $initialStock);

        // 2. Create order via Requisition (status = pending)
        $selected = [$this->p1->id];
        $requisitionComponent = Livewire::test(Requisition::class)
            ->set('selected', $selected)
            ->set('supplier_id', $this->supplier->id);
        $requisitionComponent->call('createOrder');

        // Confirm purchase was created with status pending
        $purchase = Purchase::where('status', 'pending')->first();
        $this->assertNotNull($purchase);
        $this->assertEquals('pending', $purchase->status);

        // Confirm stock is STILL 5 (has not changed)
        $this->p1->refresh();
        $this->assertEquals(5, $this->p1->stock_qty);

        // 3. Save pending purchase order via Purchases component (status = pending)
        // Let's test that storeOrder does not affect stock
        $purchasesComponent = Livewire::test(\App\Livewire\Purchases::class);
        $cartItem = [
            'id' => md5($this->p1->id),
            'pid' => $this->p1->id,
            'name' => $this->p1->name,
            'cost' => $this->p1->cost,
            'price' => $this->p1->price,
            'qty' => 3,
            'total' => 3 * $this->p1->cost,
            'is_variable' => false,
            'items' => [],
            'flete' => [
                'total_flete' => 0,
                'flete_producto' => 0,
                'valor_flete' => $this->p1->cost,
                'nuevo_total' => 3 * $this->p1->cost
            ]
        ];
        
        $purchasesComponent->set('cart', collect([$cartItem]))
            ->call('save')
            ->call('setCustomer', ['id' => $this->supplier->id, 'name' => $this->supplier->name])
            ->set('totalCart', 3 * $this->p1->cost)
            ->call('storeOrder');


        // Confirm a second pending purchase is created
        $this->assertEquals(2, Purchase::where('status', 'pending')->count());

        // Confirm stock is STILL 5 (storeOrder did NOT increase stock)
        $this->p1->refresh();
        $this->assertEquals(5, $this->p1->stock_qty);

        // 4. Load the first pending order (Requisition) to the cart and finalize it
        $firstPurchase = Purchase::where('notes', 'Generado desde Requisición')->first();
        
        $purchasesComponent2 = Livewire::test(\App\Livewire\Purchases::class)
            ->call('loadOrderToCart', $firstPurchase->id)
            ->call('setCustomer', ['id' => $this->supplier->id, 'name' => $this->supplier->name]);

        // Confirm order_selected_id is set
        $this->assertEquals($firstPurchase->id, $purchasesComponent2->get('order_selected_id'));

        // Save/Finalize purchase
        $purchasesComponent2->set('purchaseType', 'cash')
            ->call('Store');


        // Confirm the purchase status was updated to paid
        $firstPurchase->refresh();
        $this->assertEquals('paid', $firstPurchase->status);

        // Confirm stock has been incremented exactly once (5 + deficit)
        // Deficit calculated in Requisition: max_stock (10) - stock_qty (5) = 5.
        // So new stock should be 5 + 5 = 10.
        $this->p1->refresh();
        $this->assertEquals(10, $this->p1->stock_qty);

        // Confirm that NO new purchase was created (we updated the existing one instead of duplicating)
        $this->assertEquals(2, Purchase::count());
    }
}
