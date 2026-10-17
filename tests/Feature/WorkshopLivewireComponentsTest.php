<?php

namespace Tests\Feature;

use App\Livewire\Workshop\MechanicCommissions;
use App\Livewire\Workshop\VehicleHistory;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WorkshopLivewireComponentsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $mechanic;
    protected $customer;
    protected $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->mechanic = User::factory()->create([
            'name' => 'Luis Mecanico',
            'email' => 'luis.mecanico@test.com',
            'profile' => 'Mecanico',
        ]);

        $this->customer = Customer::create([
            'name' => 'DISTRIBUIDORA EL VIGIA C.A.',
            'taxpayer_id' => 'J-31415926-5',
            'phone' => '0275-8812345',
            'address' => 'Zona Industrial, El Vigia',
        ]);

        $this->vehicle = Vehicle::create([
            'customer_id' => $this->customer->id,
            'plate' => 'VIG-789',
            'brand' => 'CHEVROLET',
            'model' => 'LUV D-MAX',
            'year' => 2012,
            'current_mileage' => 180000,
        ]);
    }

    public function test_mechanic_commissions_component_can_render_and_filter()
    {
        $order = WorkOrder::create([
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'user_id' => $this->user->id,
            'status' => 'completed',
            'initial_diagnosis' => 'Revision de frenos traseros',
        ]);

        WorkOrderItem::create([
            'work_order_id' => $order->id,
            'mechanic_id' => $this->mechanic->id,
            'type' => 'labor',
            'description' => 'Rectificado de tambores traseros',
            'quantity' => 1,
            'unit_price' => 60.00,
            'mechanic_commission_percentage' => 30.00,
        ]);

        Livewire::actingAs($this->user)
            ->test(MechanicCommissions::class)
            ->assertStatus(200)
            ->assertSee('Comisiones')
            ->assertSee('LUIS MECANICO')
            ->set('mechanicId', $this->mechanic->id)
            ->assertSee('$18.00'); // 60 * 30% = 18
    }

    public function test_vehicle_history_component_can_search_and_display_history()
    {
        $order = WorkOrder::create([
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'user_id' => $this->user->id,
            'status' => 'completed',
            'initial_diagnosis' => 'Cambio de kit de embrague',
        ]);

        WorkOrderItem::create([
            'work_order_id' => $order->id,
            'type' => 'labor',
            'description' => 'Bajada y montaje de caja',
            'quantity' => 1,
            'unit_price' => 120.00,
        ]);

        Livewire::actingAs($this->user)
            ->test(VehicleHistory::class)
            ->assertStatus(200)
            ->assertSee('Historial')
            ->set('searchPlate', 'VIG-789')
            ->call('selectVehicle', $this->vehicle->id)
            ->assertSee('VIG-789')
            ->assertSee('DISTRIBUIDORA EL VIGIA C.A.')
            ->assertSee('CHEVROLET LUV D-MAX')
            ->assertSee('Bajada y montaje de caja');
    }
}