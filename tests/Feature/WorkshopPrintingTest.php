<?php

namespace Tests\Feature;

use App\Models\Configuration;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Services\WorkshopPrintService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkshopPrintingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Configuration::create([
            'business_name' => 'Taller Bucaro C.A.',
            'taxpayer_id' => 'J-12345678-9',
            'phone' => '0275-1234567',
            'address' => 'Zona Industrial El Vigia',
        ]);
    }

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

    public function test_can_generate_escpos_thermal_ticket_for_reception(): void
    {
        $user = User::factory()->create(['name' => 'Asesor Taller']);
        $customer = $this->createCustomer();
        $vehicle = $this->createVehicle($customer);

        $order = WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'user_id' => $user->id,
            'entry_mileage' => 455850,
            'fuel_level' => '1/2',
            'initial_diagnosis' => 'Ruido en suspension y cambio de filtros',
        ]);

        $service = new WorkshopPrintService();
        $ticket = $service->buildThermalTicket($order, 'reception', 80);

        $this->assertNotEmpty($ticket);
        $this->assertStringContainsString($order->order_number, $ticket);
        $this->assertStringContainsString('24T LAH', $ticket);
        $this->assertStringContainsString('AGUAS DE MERIDA', $ticket);
        $this->assertStringContainsString('455,850', $ticket);
        $this->assertStringContainsString('1/2', $ticket);
        $this->assertStringContainsString('FICHA DE RECEPCION', $ticket);
    }

    public function test_can_generate_escpos_thermal_ticket_for_delivery_with_items_breakdown(): void
    {
        $user = User::factory()->create();
        $customer = $this->createCustomer();
        $vehicle = $this->createVehicle($customer);

        $order = WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'user_id' => $user->id,
            'entry_mileage' => 455850,
            'initial_diagnosis' => 'Mantenimiento',
        ]);

        $order->addItem([
            'type' => 'part',
            'description' => 'Filtro de Aceite',
            'quantity' => 1,
            'unit_price' => 12.00,
        ]);

        $order->addItem([
            'type' => 'labor',
            'description' => 'Servicio de Cambio',
            'quantity' => 1,
            'unit_price' => 18.00,
        ]);

        $order->refresh();

        $service = new WorkshopPrintService();
        $ticket = $service->buildThermalTicket($order, 'delivery', 80);

        $this->assertNotEmpty($ticket);
        $this->assertStringContainsString('COMPROBANTE DE ENTREGA', $ticket);
        $this->assertStringContainsString('Filtro de Aceite', $ticket);
        $this->assertStringContainsString('Servicio de Cambio', $ticket);
        $this->assertStringContainsString('30.00', $ticket);
    }

    public function test_can_download_workshop_work_order_pdf(): void
    {
        $user = User::factory()->create();
        $customer = $this->createCustomer();
        $vehicle = $this->createVehicle($customer);

        $order = WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'user_id' => $user->id,
            'entry_mileage' => 455850,
            'initial_diagnosis' => 'Diagnostico',
        ]);

        $response = $this->actingAs($user)
            ->get(route('workshop.work-orders.pdf', ['order' => $order->id]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
