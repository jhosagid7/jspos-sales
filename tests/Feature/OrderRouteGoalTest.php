<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Category;
use App\Models\Warehouse;
use App\Models\Configuration;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Livewire\Sales;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class OrderRouteGoalTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $driverUser;
    protected $customer;
    protected $warehouse;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset static cache in ConfigurationService
        $ref = new \ReflectionClass(\App\Services\ConfigurationService::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null);

        // Setup Configuration
        Configuration::create([
            'business_name' => 'Test Business',
            'bcv_rate' => 36.50,
            'binance_rate' => 38.00,
        ]);

        // Seed currencies
        $this->seed(\Database\Seeders\CurrencySeeder::class);

        // Create Admin User
        $this->adminUser = User::factory()->create();
        $adminRole = Role::findOrCreate('Admin');
        $this->adminUser->assignRole($adminRole);
        
        // Give permissions to Admin User
        Permission::findOrCreate('orders.view_all');
        Permission::findOrCreate('orders.add_to_cart');
        Permission::findOrCreate('orders.edit');
        Permission::findOrCreate('orders.delete');
        Permission::findOrCreate('sales.create');
        Permission::findOrCreate('sales.pdf');
        
        $this->adminUser->givePermissionTo([
            'orders.view_all',
            'orders.add_to_cart',
            'orders.edit',
            'orders.delete',
            'sales.create',
            'sales.pdf'
        ]);

        // Create Driver User
        $this->driverUser = User::factory()->create([
            'name' => 'Chofer Test',
            'profile' => 'Driver',
            'route_goal' => 1000.00
        ]);

        // Create driver role
        $driverRole = Role::findOrCreate('Driver');
        $this->driverUser->assignRole($driverRole);

        // Create Customer
        $this->customer = Customer::create([
            'name' => 'Test Route Customer',
        ]);

        // Create Supplier
        $supplier = Supplier::create([
            'name' => 'Test Supplier',
            'taxpayer_id' => 'J88888888',
            'address' => 'Supplier Address',
            'phone' => '0212-2222222',
        ]);

        // Create Warehouse
        $this->warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
        ]);

        // Create Product
        $category = Category::create([
            'name' => 'Test Category',
        ]);
        $this->product = Product::create([
            'name' => 'Test Route Product',
            'sku' => 'ROUTE-PROD',
            'cost' => 10.00,
            'price' => 20.00,
            'manage_stock' => false,
            'stock_qty' => 100,
            'low_stock' => 0,
            'supplier_id' => $supplier->id,
            'category_id' => $category->id,
        ]);
    }

    public function test_driver_route_goal_persists()
    {
        $this->assertEquals(1000.00, floatval($this->driverUser->route_goal));

        $this->driverUser->update(['route_goal' => 1500.50]);
        $this->assertEquals(1500.50, floatval($this->driverUser->fresh()->route_goal));
    }

    public function test_order_surcharges_physical_saving()
    {
        $order = Order::create([
            'user_id' => $this->adminUser->id,
            'customer_id' => $this->customer->id,
            'driver_id' => $this->driverUser->id,
            'total' => 110.00,
            'items' => 1,
            'status' => 'pending',
            'apply_commissions' => true,
            'apply_freight' => true,
            'base_amount' => 100.00,
            'commission_amount' => 5.00,
            'freight_amount' => 3.00,
            'exchange_diff_amount' => 2.00,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'driver_id' => $this->driverUser->id,
            'total' => 110.00,
            'base_amount' => 100.00,
            'commission_amount' => 5.00,
            'freight_amount' => 3.00,
            'exchange_diff_amount' => 2.00,
        ]);

        $loadedOrder = Order::find($order->id);
        $this->assertEquals(100.00, floatval($loadedOrder->base_amount));
        $this->assertEquals(5.00, floatval($loadedOrder->commission_amount));
        $this->assertEquals(3.00, floatval($loadedOrder->freight_amount));
        $this->assertEquals(2.00, floatval($loadedOrder->exchange_diff_amount));
        $this->assertEquals($this->driverUser->id, $loadedOrder->driver->id);
    }

    public function test_sales_livewire_driver_filtering_and_totals()
    {
        // Create an order assigned to the driver
        $order = Order::create([
            'user_id' => $this->adminUser->id,
            'customer_id' => $this->customer->id,
            'driver_id' => $this->driverUser->id,
            'total' => 220.00,
            'items' => 1,
            'status' => 'pending',
            'apply_commissions' => true,
            'apply_freight' => true,
            'base_amount' => 200.00,
            'commission_amount' => 10.00,
            'freight_amount' => 6.00,
            'exchange_diff_amount' => 4.00,
        ]);

        OrderDetail::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'regular_price' => 200.00,
            'sale_price' => 200.00,
            'quantity' => 1,
            'discount' => 0.00,
        ]);

        // Test Livewire component
        Livewire::actingAs($this->adminUser)
            ->test(Sales::class)
            ->set('searchDriver', $this->driverUser->id)
            ->call('getOrdersWithDetails')
            ->assertSet('ordersTotal', 200.00)
            ->assertSet('ordersCommissionTotal', 10.00)
            ->assertSet('ordersFreightTotal', 6.00)
            ->assertSet('ordersDiffTotal', 4.00)
            ->assertSet('ordersGrandTotal', 220.00);
    }
}
