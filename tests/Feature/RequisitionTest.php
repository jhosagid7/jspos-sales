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
}
