<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Services\WorkOrderBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderBillingTest extends TestCase
{
    use RefreshDatabase;

    protected function createCategory(): Category
    {
        return Category::create(['name' => 'Mantenimiento']);
    }

    protected function createSupplier(): Supplier
    {
        return Supplier::create(['name' => 'Distribuidora Automotriz']);
    }

    protected function createCustomer(array $attributes = []): Customer
    {
        return Customer::create(array_merge([
            'name' => 'Aguas de Merida C.A.',
            'taxpayer_id' => 'G-20007690-9',
            'address' => 'Av. Universidad, Merida',
            'phone' => '0274-2401111',
            'allow_credit' => false,
        ], $attributes));
    }

    protected function createVehicle(Customer $customer, array $attributes = []): Vehicle
    {
        return Vehicle::create(array_merge([
            'customer_id' => $customer->id,
            'plate' => '24T LAH',
            'brand' => 'Ford',
            'model' => 'F-150 Fortaleza',
            'year' => 2007,
            'current_mileage' => 455800,
        ], $attributes));
    }

    protected function createProduct(array $attributes = []): Product
    {
        $cat = $this->createCategory();
        $sup = $this->createSupplier();

        return Product::create(array_merge([
            'name' => 'Aceite 20W50',
            'cost' => 25.00,
            'price' => 35.00,
            'manage_stock' => 1,
            'stock_qty' => 10,
            'low_stock' => 2,
            'category_id' => $cat->id,
            'supplier_id' => $sup->id,
            'type' => 'physical',
        ], $attributes));
    }

    public function test_can_bill_work_order_with_immediate_cash_payment(): void
    {
        $user = User::factory()->create(['name' => 'Cajero Taller']);
        $mechanic = User::factory()->create(['name' => 'Mecanico Jose']);
        $customer = $this->createCustomer();
        $vehicle = $this->createVehicle($customer);
        $product = $this->createProduct(['stock_qty' => 10]);

        $order = WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'user_id' => $user->id,
            'mechanic_id' => $mechanic->id,
            'entry_mileage' => 455800,
            'initial_diagnosis' => 'Cambio de aceite y frenos',
        ]);

        // Add part with linked physical product (qty: 2)
        $order->addItem([
            'type' => 'part',
            'product_id' => $product->id,
            'description' => 'Aceite 20W50 (Galon)',
            'quantity' => 2,
            'unit_price' => 35.00,
            'unit_cost' => 25.00,
        ]);

        // Add labor
        $order->addItem([
            'type' => 'labor',
            'description' => 'Mano de obra cambio de aceite',
            'quantity' => 1,
            'unit_price' => 15.00,
            'mechanic_id' => $mechanic->id,
            'mechanic_commission_percentage' => 40.00,
        ]);

        $service = new WorkOrderBillingService();
        $sale = $service->bill($order, [
            'user_id' => $user->id,
            'payment_method' => 'cash_usd',
            'exit_mileage' => 455850,
        ]);

        $this->assertInstanceOf(Sale::class, $sale);
        $this->assertEquals(85.00, (float)$sale->total); // (35 * 2) + 15 = 85
        $this->assertEquals('paid', $sale->status);
        $this->assertEquals('cash', $sale->type);

        // Verify product inventory was decremented
        $product->refresh();
        $this->assertEquals(8, $product->stock_qty); // 10 - 2 = 8

        // Verify payment record was created
        $this->assertDatabaseHas('payments', [
            'sale_id' => $sale->id,
            'amount' => 85.00,
            'pay_way' => 'cash',
        ]);

        // Verify work order updated
        $order->refresh();
        $this->assertEquals('invoiced', $order->status);
        $this->assertEquals($sale->id, $order->sale_id);
        $this->assertEquals(455850, $order->exit_mileage);
        $this->assertNotNull($order->delivered_at);

        // Verify vehicle mileage updated
        $vehicle->refresh();
        $this->assertEquals(455850, $vehicle->current_mileage);
    }

    public function test_can_bill_work_order_on_credit_for_approved_customer(): void
    {
        $user = User::factory()->create();
        $customer = $this->createCustomer(['allow_credit' => true, 'credit_days' => 15]);
        $vehicle = $this->createVehicle($customer);

        $order = WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'user_id' => $user->id,
            'entry_mileage' => 455800,
            'initial_diagnosis' => 'Reparacion mayor',
        ]);

        $order->addItem([
            'type' => 'labor',
            'description' => 'Diagnostico computarizado',
            'quantity' => 1,
            'unit_price' => 50.00,
        ]);

        $service = new WorkOrderBillingService();
        $sale = $service->bill($order, [
            'user_id' => $user->id,
            'payment_method' => 'credit',
            'exit_mileage' => 455810,
        ]);

        $this->assertEquals('pending', $sale->status);
        $this->assertEquals('credit', $sale->type);
        $this->assertEquals(50.00, (float)$sale->total);

        // On credit, NO immediate payment is registered
        $this->assertEquals(0, Payment::where('sale_id', $sale->id)->count());

        $order->refresh();
        $this->assertEquals('invoiced', $order->status);
    }

    public function test_cannot_bill_on_credit_if_customer_credit_is_not_allowed(): void
    {
        $user = User::factory()->create();
        $customer = $this->createCustomer(['allow_credit' => false]);
        $vehicle = $this->createVehicle($customer);

        $order = WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'user_id' => $user->id,
            'entry_mileage' => 10000,
            'initial_diagnosis' => 'Revision',
        ]);

        $order->addItem([
            'type' => 'labor',
            'description' => 'Revision',
            'quantity' => 1,
            'unit_price' => 20.00,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $service = new WorkOrderBillingService();
        $service->bill($order, [
            'user_id' => $user->id,
            'payment_method' => 'credit',
        ]);
    }

    public function test_cannot_bill_work_order_twice(): void
    {
        $user = User::factory()->create();
        $customer = $this->createCustomer();
        $vehicle = $this->createVehicle($customer);

        $order = WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'user_id' => $user->id,
            'entry_mileage' => 10000,
            'initial_diagnosis' => 'Revision',
        ]);

        $order->addItem([
            'type' => 'labor',
            'description' => 'Servicio',
            'quantity' => 1,
            'unit_price' => 20.00,
        ]);

        $service = new WorkOrderBillingService();
        $service->bill($order, [
            'user_id' => $user->id,
            'payment_method' => 'cash_usd',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        // Attempting to bill again should fail
        $service->bill($order, [
            'user_id' => $user->id,
            'payment_method' => 'cash_usd',
        ]);
    }

    public function test_sales_pos_can_load_work_order_to_cart_and_link_vehicle(): void
    {
        $user = User::factory()->create();
        $warehouse = \App\Models\Warehouse::firstOrCreate(
            ['id' => 1],
            ['name' => 'Almacen Principal', 'is_active' => 1]
        );
        $customer = $this->createCustomer(['allow_credit' => true]);
        $vehicle = $this->createVehicle($customer);
        $part = $this->createProduct(['price' => 45.00, 'manage_stock' => 0]);

        $order = WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'user_id' => $user->id,
            'entry_mileage' => 125000,
            'initial_diagnosis' => 'Frenos y suspension',
        ]);

        $order->addItem([
            'type' => 'part',
            'product_id' => $part->id,
            'description' => 'Pastillas de Freno',
            'quantity' => 1,
            'unit_price' => 45.00,
        ]);

        $order->addItem([
            'type' => 'labor',
            'description' => 'Mano de obra instalacion',
            'quantity' => 1,
            'unit_price' => 25.00,
        ]);

        // Test loading work order into POS
        $component = \Livewire\Livewire::actingAs($user)
            ->test(\App\Livewire\Sales::class)
            ->call('loadWorkOrderToCart', $order->id)
            ->assertSet('work_order_id', $order->id)
            ->assertSet('work_order_vehicle.id', $vehicle->id);

        $this->assertEquals($customer->id, $component->get('customer')['id']);
        $this->assertCount(2, $component->get('cart'));
    }
}
