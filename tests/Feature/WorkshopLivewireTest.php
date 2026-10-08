<?php

namespace Tests\Feature;

use App\Livewire\Workshop\WorkOrders;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WorkshopLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected function createCustomer(array $attributes = []): Customer
    {
        return Customer::create(array_merge([
            'name' => 'Aguas de Merida C.A.',
            'taxpayer_id' => 'G-20007690-9',
            'address' => 'Av. Universidad',
            'phone' => '0274-1234567',
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

    public function test_component_can_render(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(WorkOrders::class)
            ->assertStatus(200);
    }

    public function test_can_filter_and_search_work_orders(): void
    {
        $user = User::factory()->create();
        $customer1 = $this->createCustomer(['name' => 'Transportes Vigia']);
        $vehicle1 = $this->createVehicle($customer1, ['plate' => 'A11BB22']);

        $customer2 = $this->createCustomer(['name' => 'Particular Juan']);
        $vehicle2 = $this->createVehicle($customer2, ['plate' => 'XYZ999']);

        $order1 = WorkOrder::create([
            'customer_id' => $customer1->id,
            'vehicle_id' => $vehicle1->id,
            'user_id' => $user->id,
            'status' => 'reception',
            'entry_mileage' => 10000,
            'initial_diagnosis' => 'Frenos',
        ]);

        $order2 = WorkOrder::create([
            'customer_id' => $customer2->id,
            'vehicle_id' => $vehicle2->id,
            'user_id' => $user->id,
            'status' => 'ready',
            'entry_mileage' => 20000,
            'initial_diagnosis' => 'Motor',
        ]);

        Livewire::actingAs($user)
            ->test(WorkOrders::class)
            ->set('search', 'A11BB22')
            ->assertSee('A11BB22')
            ->assertDontSee('XYZ999')
            ->set('search', '')
            ->set('statusFilter', 'ready')
            ->assertSee('XYZ999')
            ->assertDontSee('A11BB22');
    }

    public function test_can_create_new_work_order(): void
    {
        $user = User::factory()->create();
        $customer = $this->createCustomer();
        $vehicle = $this->createVehicle($customer);

        Livewire::actingAs($user)
            ->test(WorkOrders::class)
            ->set('newOrder.customer_id', $customer->id)
            ->set('newOrder.vehicle_id', $vehicle->id)
            ->set('newOrder.entry_mileage', 455900)
            ->set('newOrder.fuel_level', '1/2')
            ->set('newOrder.initial_diagnosis', 'Revision de suspension y tren delantero')
            ->call('storeOrder')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('work_orders', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'entry_mileage' => 455900,
            'fuel_level' => '1/2',
            'status' => 'reception',
        ]);
    }

    public function test_can_add_item_to_work_order(): void
    {
        $user = User::factory()->create();
        $mechanic = User::factory()->create(['name' => 'Mecanico Jose']);
        $customer = $this->createCustomer();
        $vehicle = $this->createVehicle($customer);

        $order = WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'user_id' => $user->id,
            'entry_mileage' => 10000,
            'initial_diagnosis' => 'Revision',
        ]);

        Livewire::actingAs($user)
            ->test(WorkOrders::class)
            ->call('selectOrder', $order->id)
            ->set('newItem.type', 'labor')
            ->set('newItem.description', 'Cambio de correa de tiempo')
            ->set('newItem.quantity', 1)
            ->set('newItem.unit_price', 60.00)
            ->set('newItem.mechanic_id', $mechanic->id)
            ->set('newItem.mechanic_commission_percentage', 35.00)
            ->call('addItem')
            ->assertHasNoErrors();

        $order->refresh();
        $this->assertEquals(60.00, (float)$order->total);
        $this->assertEquals(60.00, (float)$order->subtotal_labor);
        $this->assertEquals(21.00, (float)$order->items->first()->mechanic_commission_amount); // 35% of 60
    }

    public function test_can_change_work_order_status(): void
    {
        $user = User::factory()->create();
        $customer = $this->createCustomer();
        $vehicle = $this->createVehicle($customer);

        $order = WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'user_id' => $user->id,
            'entry_mileage' => 10000,
            'status' => 'reception',
            'initial_diagnosis' => 'Revision',
        ]);

        Livewire::actingAs($user)
            ->test(WorkOrders::class)
            ->call('changeStatus', $order->id, 'in_progress');

        $order->refresh();
        $this->assertEquals('in_progress', $order->status);
    }

    public function test_can_bill_work_order_via_livewire(): void
    {
        $user = User::factory()->create();
        $customer = $this->createCustomer();
        $vehicle = $this->createVehicle($customer);

        $order = WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'user_id' => $user->id,
            'entry_mileage' => 10000,
            'status' => 'ready',
            'initial_diagnosis' => 'Revision',
        ]);

        $order->addItem([
            'type' => 'labor',
            'description' => 'Servicio Rapido',
            'quantity' => 1,
            'unit_price' => 30.00,
        ]);

        Livewire::actingAs($user)
            ->test(WorkOrders::class)
            ->call('billOrder', $order->id, 'cash_usd', 10050)
            ->assertHasNoErrors();

        $order->refresh();
        $this->assertEquals('invoiced', $order->status);
        $this->assertNotNull($order->sale_id);
        $this->assertEquals(10050, $order->exit_mileage);
    }
}
