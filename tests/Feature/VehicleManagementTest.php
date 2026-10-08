<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function createCustomer(array $attributes = []): Customer
    {
        return Customer::create(array_merge([
            'name' => 'Cliente Taller Prueba',
            'taxpayer_id' => 'V-12345678',
            'address' => 'Zona Industrial El Vigia',
            'phone' => '04140000000',
        ], $attributes));
    }

    public function test_can_create_vehicle_associated_with_a_customer(): void
    {
        $customer = $this->createCustomer([
            'name' => 'Aguas de Merida C.A.',
            'taxpayer_id' => 'G-20007690-9',
        ]);

        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate' => '24t lah',
            'brand' => 'Ford',
            'model' => 'F150 Fortaleza 4.2L',
            'year' => 2007,
            'color' => 'Blanco',
            'current_mileage' => 455811,
            'fuel_type' => 'gasolina',
            'transmission' => 'manual',
            'notes' => 'Unidad recuperada de flota de Búcaros',
        ]);

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'customer_id' => $customer->id,
            'plate' => '24T LAH',
            'brand' => 'Ford',
            'model' => 'F150 Fortaleza 4.2L',
        ]);

        $this->assertEquals($customer->id, $vehicle->customer->id);
        $this->assertTrue($customer->vehicles->contains($vehicle));
    }

    public function test_vehicle_plate_is_properly_formatted_and_uppercased(): void
    {
        $customer = $this->createCustomer();

        $vehicle = new Vehicle([
            'customer_id' => $customer->id,
            'plate' => '  a87bv1g  ',
            'brand' => 'DONGFENG',
            'model' => 'DUOLIKA 5T',
        ]);
        $vehicle->save();

        $this->assertEquals('A87BV1G', $vehicle->plate);
    }

    public function test_vehicle_search_scope_finds_by_plate_brand_model_or_customer(): void
    {
        $customer = $this->createCustomer(['name' => 'Transportes Los Andes']);

        Vehicle::create([
            'customer_id' => $customer->id,
            'plate' => 'AE4144T',
            'brand' => 'Bera',
            'model' => 'BR-200 Carguero',
            'year' => 2013,
            'color' => 'Azul',
        ]);

        Vehicle::create([
            'customer_id' => $customer->id,
            'plate' => 'XYZ999',
            'brand' => 'Toyota',
            'model' => 'Land Cruiser',
            'year' => 2014,
            'color' => 'Blanco',
        ]);

        $this->assertCount(1, Vehicle::search('AE4144')->get());
        $this->assertCount(1, Vehicle::search('Cruiser')->get());
        $this->assertCount(2, Vehicle::search('Los Andes')->get());
    }

    public function test_vehicle_returns_descriptive_full_name(): void
    {
        $customer = $this->createCustomer();

        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate' => 'MFD10E',
            'brand' => 'Toyota',
            'model' => 'Land Cruiser Serie 70',
            'year' => 2014,
            'color' => 'Blanco',
        ]);

        $this->assertEquals('Toyota Land Cruiser Serie 70 2014 [MFD10E]', $vehicle->full_name);
    }
}
