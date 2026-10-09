<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Services\WorkshopMechanicCommissionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WorkshopCommissionAndHistoryTest extends TestCase
{
    use DatabaseTransactions;

    protected $service;
    protected $mechanic1;
    protected $mechanic2;
    protected $customer;
    protected $vehicle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WorkshopMechanicCommissionService();

        $this->mechanic1 = User::factory()->create([
            'name' => 'Carlos Mecanico',
            'email' => 'carlos.mecanico@test.com',
            'profile' => 'Mecanico',
        ]);

        $this->mechanic2 = User::factory()->create([
            'name' => 'Pedro Especialista',
            'email' => 'pedro.especialista@test.com',
            'profile' => 'Mecanico',
        ]);

        $this->customer = Customer::create([
            'name' => 'TRANSPORTES LOS ANDES C.A.',
            'taxpayer_id' => 'J-12345678-9',
            'phone' => '0275-8812345',
            'address' => 'El Vigia, Merida',
        ]);

        $this->vehicle = Vehicle::create([
            'customer_id' => $this->customer->id,
            'plate' => 'ABC-123',
            'brand' => 'TOYOTA',
            'model' => 'HILUX 4X4',
            'year' => 2021,
            'current_mileage' => 95000,
        ]);
    }

    public function test_can_calculate_mechanic_commissions_by_date_and_mechanic()
    {
        $order = WorkOrder::create([
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'user_id' => $this->mechanic1->id,
            'status' => 'completed',
            'initial_diagnosis' => 'Servicio general de frenos y suspension',
        ]);

        // Item 1: Labor assigned to mechanic 1 with 30% commission
        WorkOrderItem::create([
            'work_order_id' => $order->id,
            'mechanic_id' => $this->mechanic1->id,
            'type' => 'labor',
            'description' => 'Cambio de pastillas y rectificado de discos',
            'quantity' => 1,
            'unit_price' => 50.00,
            'mechanic_commission_percentage' => 30.00,
        ]);

        // Item 2: Labor assigned to mechanic 2 with 25% commission
        WorkOrderItem::create([
            'work_order_id' => $order->id,
            'mechanic_id' => $this->mechanic2->id,
            'type' => 'labor',
            'description' => 'Mantenimiento del tren delantero',
            'quantity' => 1,
            'unit_price' => 80.00,
            'mechanic_commission_percentage' => 25.00,
        ]);

        // Item 3: Part item (should not generate mechanic commission)
        WorkOrderItem::create([
            'work_order_id' => $order->id,
            'type' => 'part',
            'description' => 'Pastillas de freno cerÃ¡micas',
            'quantity' => 1,
            'unit_price' => 45.00,
        ]);

        $order->recalculateTotals();

        // 1. Overall report
        $report = $this->service->getCommissionReport(
            null,
            Carbon::now()->subDay()->format('Y-m-d'),
            Carbon::now()->addDay()->format('Y-m-d')
        );

        $this->assertEquals(130.00, $report['total_labor_charged']);
        $this->assertEquals(35.00, $report['total_commission_earned']); // (50 * 0.3) + (80 * 0.25) = 15 + 20 = 35
        $this->assertEquals(2, $report['total_items_count']);
        $this->assertCount(2, $report['by_mechanic']);

        // 2. Specific filter for Mechanic 1
        $mechanic1Report = $this->service->getCommissionReport(
            $this->mechanic1->id,
            Carbon::now()->subDay()->format('Y-m-d'),
            Carbon::now()->addDay()->format('Y-m-d')
        );

        $this->assertEquals(50.00, $mechanic1Report['total_labor_charged']);
        $this->assertEquals(15.00, $mechanic1Report['total_commission_earned']);
        $this->assertEquals(1, $mechanic1Report['total_items_count']);
    }

    public function test_can_retrieve_vehicle_service_history()
    {
        // First order
        $order1 = WorkOrder::create([
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'user_id' => $this->mechanic1->id,
            'status' => 'invoiced',
            'entry_mileage' => 90000,
            'initial_diagnosis' => 'Mantenimiento preventivo 90K km',
        ]);
        WorkOrderItem::create([
            'work_order_id' => $order1->id,
            'type' => 'labor',
            'description' => 'Cambio de aceite y filtros',
            'quantity' => 1,
            'unit_price' => 30.00,
        ]);
        WorkOrderItem::create([
            'work_order_id' => $order1->id,
            'type' => 'part',
            'description' => 'Aceite sintetico 5W-30',
            'quantity' => 4,
            'unit_price' => 10.00,
        ]);
        $order1->recalculateTotals();

        // Second order
        $order2 = WorkOrder::create([
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'user_id' => $this->mechanic2->id,
            'status' => 'completed',
            'entry_mileage' => 95000,
            'initial_diagnosis' => 'Revision de aire acondicionado',
        ]);
        WorkOrderItem::create([
            'work_order_id' => $order2->id,
            'type' => 'labor',
            'description' => 'Carga de gas refrigerante R134a',
            'quantity' => 1,
            'unit_price' => 40.00,
        ]);
        $order2->recalculateTotals();

        // Query by plate
        $history = $this->service->getVehicleServiceHistory('ABC-123');

        $this->assertNotNull($history['vehicle']);
        $this->assertEquals('ABC-123', $history['vehicle']->plate);
        $this->assertEquals(2, $history['total_visits']);
        $this->assertEquals(110.00, $history['total_spent']); // (30 + 40) + 40 = 70 + 40 = 110
        $this->assertEquals(40.00, $history['total_parts_spent']);
        $this->assertEquals(70.00, $history['total_labor_spent']);
        $this->assertCount(2, $history['orders']);
    }
}