<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\BuyingTrendService;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BuyingTrendServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        // This should fail because BuyingTrendService does not exist yet
        $this->service = new BuyingTrendService();
    }

    public function test_it_returns_empty_collection_for_customer_without_history()
    {
        $customer = Customer::create([
            'name' => 'Test Customer',
            'taxpayer_id' => '123',
            'address' => 'Test Address',
            'city' => 'Test City',
            'type' => 'Consumidor Final'
        ]);
        
        $trends = $this->service->getTrends($customer->id, 1);
        
        $this->assertCount(0, $trends);
    }

    public function test_it_prioritizes_recent_purchases_over_historical_frequency()
    {
        $category = \App\Models\Category::create(['name' => 'Test Category']);
        $supplier = \App\Models\Supplier::create(['name' => 'Test Supplier', 'phone' => '123', 'address' => 'A']);
        $warehouse = \App\Models\Warehouse::create(['name' => 'Test Warehouse', 'address' => 'A']);
        $warehouseId = $warehouse->id;

        $customer = Customer::create(['name' => 'Test', 'taxpayer_id' => '1', 'address' => 'A', 'city' => 'C', 'type' => 'Mayoristas']);
        $user = \App\Models\User::first() ?? \App\Models\User::factory()->create();

        // Product A: 10 historical purchases
        $productA = Product::create([
            'name' => 'Product A', 
            'sku' => 'A', 
            'price' => 10, 
            'cost' => 5, 
            'stock_qty' => 100,
            'type' => 'physical',
            'status' => 'available',
            'manage_stock' => 1,
            'low_stock' => 1,
            'supplier_id' => $supplier->id,
            'category_id' => $category->id
        ]);
        $productA->warehouses()->attach($warehouseId, ['stock_qty' => 100]);

        // Product B: 4 recent purchases
        $productB = Product::create([
            'name' => 'Product B', 
            'sku' => 'B', 
            'price' => 10, 
            'cost' => 5, 
            'stock_qty' => 100,
            'type' => 'physical',
            'status' => 'available',
            'manage_stock' => 1,
            'low_stock' => 1,
            'supplier_id' => $supplier->id,
            'category_id' => $category->id
        ]);
        $productB->warehouses()->attach($warehouseId, ['stock_qty' => 100]);

        // Create historical sales for A (91 days ago)
        for ($i = 0; $i < 10; $i++) {
            $sale = Sale::create([
                'total' => 10, 
                'items' => 1, 
                'customer_id' => $customer->id, 
                'user_id' => $user->id, 
                'created_at' => now()->subDays(91),
                'invoice_number' => 'HIST-'.$i,
                'order_number' => 'HORD-'.$i,
                'status' => 'paid',
                'type' => 'cash'
            ]);
            SaleDetail::create(['sale_id' => $sale->id, 'product_id' => $productA->id, 'quantity' => 1, 'regular_price' => 10, 'sale_price' => 10, 'warehouse_id' => $warehouseId, 'created_at' => now()->subDays(91), 'discount' => 0]);
        }

        // Create recent sales for B (5 days ago)
        for ($i = 0; $i < 4; $i++) {
            $sale = Sale::create([
                'total' => 10, 
                'items' => 1, 
                'customer_id' => $customer->id, 
                'user_id' => $user->id, 
                'created_at' => now()->subDays(5),
                'invoice_number' => 'RECENT-'.$i,
                'order_number' => 'RORD-'.$i,
                'status' => 'paid',
                'type' => 'cash'
            ]);
            SaleDetail::create(['sale_id' => $sale->id, 'product_id' => $productB->id, 'quantity' => 1, 'regular_price' => 10, 'sale_price' => 10, 'warehouse_id' => $warehouseId, 'created_at' => now()->subDays(5), 'discount' => 0]);
        }

        $trends = $this->service->getTrends($customer->id, $warehouseId);

        $this->assertCount(2, $trends);
        $this->assertEquals($productB->id, $trends->first()->id, 'Product B should be first due to recency weighting');
        $this->assertEquals($productA->id, $trends->last()->id);
    }
}
