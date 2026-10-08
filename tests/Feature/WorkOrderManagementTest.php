<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function createCustomer(array $attributes = []): Customer
    {
        return Customer::create(array_merge([
            'name' => 'Aguas de Merida C.A.',
            'taxpayer_id' => 'G-20007690-9',
            'address' => 'Av. Universidad, Merida',
            'phone' => '0274-2401111',
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
            'current_mileage' => 455811,
        ], $attributes));
    }

    public function test_can_create_work_order_with_consecutive_number(): void
    {
        $user = User::factory()->create(['name' => 'Recepcion Taller']);
        $customer = $this->createCustomer();
        $vehicle = $this->createVehicle($customer);

        $order1 = WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'user_id' => $user->id,
            'entry_mileage' => 455820,
            'fuel_level' => '1/2',
            'initial_diagnosis' => 'Ruido en tren delantero y cambio de aceite',
        ]);

        $this->assertNotEmpty($order1->order_number);
        $this->assertEquals('reception', $order1->status);
        $this->assertEquals($customer->id, $order1->customer->id);
        $this->assertEquals($vehicle->id, $order1->vehicle->id);

        // Next work order should have the next consecutive number
        $order2 = WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'user_id' => $user->id,
            'entry_mileage' => 456000,
            'initial_diagnosis' => 'Revision frenos',
        ]);

        $this->assertNotEquals($order1->order_number, $order2->order_number);
    }

    public function test_can_add_parts_and_labor_items_and_recalculates_totals(): void
    {
        $user = User::factory()->create();
        $mechanic = User::factory()->create(['name' => 'Mecanico Juan']);
        $customer = $this->createCustomer();
        $vehicle = $this->createVehicle($customer);

        $order = WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'user_id' => $user->id,
            'mechanic_id' => $mechanic->id,
            'entry_mileage' => 455820,
            'initial_diagnosis' => 'Mantenimiento general',
        ]);

        // Add part item
        $part = $order->addItem([
            'type' => 'part',
            'description' => 'Aceite 20W50 Mineral (4L)',
            'quantity' => 1,
            'unit_price' => 35.00,
            'unit_cost' => 25.00,
        ]);

        // Add labor item with 40% commission for mechanic
        $labor = $order->addItem([
            'type' => 'labor',
            'description' => 'Mano de obra cambio de aceite y filtro',
            'quantity' => 1,
            'unit_price' => 15.00,
            'mechanic_id' => $mechanic->id,
            'mechanic_commission_percentage' => 40.00,
        ]);

        $order->refresh();

        $this->assertEquals(35.00, (float)$part->subtotal);
        $this->assertEquals(15.00, (float)$labor->subtotal);
        $this->assertEquals(6.00, (float)$labor->mechanic_commission_amount); // 40% of 15.00

        $this->assertEquals(35.00, (float)$order->subtotal_parts);
        $this->assertEquals(15.00, (float)$order->subtotal_labor);
        $this->assertEquals(50.00, (float)$order->total);
    }

    public function test_work_order_status_transitions(): void
    {
        $user = User::factory()->create();
        $customer = $this->createCustomer();
        $vehicle = $this->createVehicle($customer);

        $order = WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'user_id' => $user->id,
            'entry_mileage' => 120000,
            'initial_diagnosis' => 'Falla de encendido',
        ]);

        $this->assertEquals('reception', $order->status);

        $order->updateStatus('budgeted');
        $this->assertEquals('budgeted', $order->status);

        $order->updateStatus('approved');
        $this->assertEquals('approved', $order->status);

        $order->updateStatus('in_progress');
        $this->assertEquals('in_progress', $order->status);

        $order->updateStatus('ready');
        $this->assertEquals('ready', $order->status);
    }

    public function test_work_order_search_scope(): void
    {
        $user = User::factory()->create();
        $customer1 = $this->createCustomer(['name' => 'Transportes Vigia C.A.']);
        $vehicle1 = $this->createVehicle($customer1, ['plate' => 'A99BB1C']);

        $customer2 = $this->createCustomer(['name' => 'Pedro Perez']);
        $vehicle2 = $this->createVehicle($customer2, ['plate' => 'XYZ123']);

        $order1 = WorkOrder::create([
            'customer_id' => $customer1->id,
            'vehicle_id' => $vehicle1->id,
            'user_id' => $user->id,
            'entry_mileage' => 50000,
            'initial_diagnosis' => 'Cambio de pastillas',
        ]);

        $order2 = WorkOrder::create([
            'customer_id' => $customer2->id,
            'vehicle_id' => $vehicle2->id,
            'user_id' => $user->id,
            'entry_mileage' => 80000,
            'initial_diagnosis' => 'Alineacion',
        ]);

        $this->assertCount(1, WorkOrder::search('A99BB1C')->get());
        $this->assertCount(1, WorkOrder::search('Pedro Perez')->get());
        $this->assertCount(1, WorkOrder::search($order1->order_number)->get());
    }
}
