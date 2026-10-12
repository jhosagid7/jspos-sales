<?php

namespace Tests\Feature;

use App\Livewire\Workshop\WorkOrders;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class WorkshopLivewireTest extends TestCase
{
    use DatabaseTransactions;

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

    public function test_can_create_quick_customer_and_quick_vehicle_from_work_orders_modal(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(WorkOrders::class)
            ->set('quickCustomer.name', 'INVERSIONES SAN CRISTOBAL')
            ->set('quickCustomer.taxpayer_id', 'J-99887766-5')
            ->set('quickCustomer.phone', '0424-7654321')
            ->call('saveQuickCustomer')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('customers', [
            'name' => 'INVERSIONES SAN CRISTOBAL',
            'taxpayer_id' => 'J-99887766-5',
        ]);

        $createdCustomer = Customer::where('taxpayer_id', 'J-99887766-5')->first();
        $this->assertNotNull($createdCustomer);
        $this->assertEquals($createdCustomer->id, $component->get('newOrder')['customer_id']);

        $component
            ->set('quickVehicle.plate', 'RAP-999')
            ->set('quickVehicle.brand', 'Ford')
            ->set('quickVehicle.model', 'F-350 Super Duty')
            ->set('quickVehicle.year', 2016)
            ->set('quickVehicle.current_mileage', 120500)
            ->call('saveQuickVehicle')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('vehicles', [
            'customer_id' => $createdCustomer->id,
            'plate' => 'RAP-999',
            'brand' => 'FORD',
            'model' => 'F-350 SUPER DUTY',
            'year' => 2016,
            'current_mileage' => 120500,
        ]);

        $createdVehicle = Vehicle::where('plate', 'RAP-999')->first();
        $this->assertNotNull($createdVehicle);
        $this->assertEquals($createdVehicle->id, $component->get('newOrder')['vehicle_id']);
        $this->assertEquals(120500, $component->get('newOrder')['entry_mileage']);
    }

    public function test_can_manage_customer_vehicles_from_customer_component(): void
    {
        $user = User::factory()->create();
        $customer = $this->createCustomer();

        $component = Livewire::actingAs($user)
            ->test(\App\Livewire\Customers::class)
            ->call('Edit', $customer->id)
            ->set('newVehicle.plate', 'MEC-123')
            ->set('newVehicle.brand', 'Chevrolet')
            ->set('newVehicle.model', 'Silverado')
            ->set('newVehicle.year', 2015)
            ->set('newVehicle.current_mileage', 210000)
            ->call('addVehicle')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('vehicles', [
            'customer_id' => $customer->id,
            'plate' => 'MEC-123',
            'brand' => 'CHEVROLET',
            'model' => 'SILVERADO',
        ]);

        $vehicle = Vehicle::where('plate', 'MEC-123')->first();
        $component->call('deleteVehicle', $vehicle->id);

        $this->assertSoftDeleted('vehicles', [
            'id' => $vehicle->id,
        ]);
    }

    public function test_can_search_customers_and_vehicles_with_smart_tokens(): void
    {
        $user = User::factory()->create();

        $c1 = Customer::create([
            'name' => 'JUAN BAUTISTA PARRA RAMIREZ',
            'taxpayer_id' => 'V-11888999',
            'phone' => '0414-5556677',
            'address' => 'El Vigia',
        ]);

        $v1 = Vehicle::create([
            'customer_id' => $c1->id,
            'plate' => 'ABC-778',
            'brand' => 'TOYOTA',
            'model' => 'FORTUNER 4X4',
            'year' => 2022,
            'color' => 'GRIS PLATA',
            'current_mileage' => 45000,
        ]);

        // Multi-token customer search in any order ("parra juan vigia")
        Livewire::actingAs($user)
            ->test(WorkOrders::class)
            ->set('showCreateModal', true)
            ->set('customerSearch', 'parra juan')
            ->assertSee('JUAN BAUTISTA PARRA RAMIREZ')
            ->call('selectCustomer', $c1->id)
            ->assertSet('newOrder.customer_id', $c1->id)
            // Multi-token vehicle search in any order ("fortuner toyota 778")
            ->set('vehicleSearch', 'toyota 778')
            ->assertSee('ABC-778')
            ->assertSee('FORTUNER 4X4')
            ->call('selectVehicle', $v1->id)
            ->assertSet('newOrder.vehicle_id', $v1->id)
            ->assertSet('newOrder.entry_mileage', 45000)
            ->call('clearVehicle')
            ->assertSet('newOrder.vehicle_id', null)
            ->call('clearCustomer')
            ->assertSet('newOrder.customer_id', null);
    }

    public function test_mechanic_selector_only_shows_users_with_mecanico_role_and_supports_smart_search(): void
    {
        $roleMecanico = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Mecanico']);

        $adminUser = User::factory()->create(['name' => 'ADMINISTRADOR GENERAL']);
        $sellerUser = User::factory()->create(['name' => 'VENDEDOR MOSTRADOR']);

        $m1 = User::factory()->create(['name' => 'PEDRO PABLO PEREZ']);
        $m1->assignRole($roleMecanico);

        $m2 = User::factory()->create(['name' => 'JOSE ALBERTO MECANICO']);
        $m2->assignRole($roleMecanico);

        Livewire::actingAs($adminUser)
            ->test(WorkOrders::class)
            ->set('showCreateModal', true)
            // Neither admin nor seller should be in mechanics list
            ->assertViewHas('mechanics', function ($mechanics) use ($adminUser, $sellerUser, $m1, $m2) {
                $ids = $mechanics->pluck('id')->toArray();
                return !in_array($adminUser->id, $ids)
                    && !in_array($sellerUser->id, $ids)
                    && in_array($m1->id, $ids)
                    && in_array($m2->id, $ids);
            })
            // Test smart multi-word search for mechanics
            ->set('mechanicSearch', 'perez pedro')
            ->assertSee('PEDRO PABLO PEREZ')
            ->assertDontSee('JOSE ALBERTO MECANICO')
            ->call('selectMechanic', $m1->id)
            ->assertSet('newOrder.mechanic_id', $m1->id)
            ->call('clearMechanic')
            ->assertSet('newOrder.mechanic_id', null);
    }
}
