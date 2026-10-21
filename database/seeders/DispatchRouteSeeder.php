<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DispatchRoute;
use App\Models\Customer;

class DispatchRouteSeeder extends Seeder
{
    public function run(): void
    {
        $routeForanea = DispatchRoute::firstOrCreate(
            ['name' => 'Ruta Foránea (Jueves)'],
            [
                'route_type' => 'fixed_route',
                'dispatch_day' => 'thursday',
                'cutoff_day' => 'wednesday',
                'cutoff_time' => '12:00:00',
                'description' => 'Carga los miércoles y viaje los jueves para zonas aledañas.',
                'is_active' => true,
            ]
        );

        DispatchRoute::firstOrCreate(
            ['name' => 'Reparto Local (Martes y Jueves)'],
            [
                'route_type' => 'local',
                'dispatch_day' => 'tuesday',
                'cutoff_day' => 'monday',
                'cutoff_time' => '17:00:00',
                'description' => 'Entregas locales programadas.',
                'is_active' => true,
            ]
        );

        DispatchRoute::firstOrCreate(
            ['name' => 'Retiro en Sede / Planta'],
            [
                'route_type' => 'pickup',
                'dispatch_day' => null,
                'cutoff_day' => null,
                'cutoff_time' => '17:00:00',
                'description' => 'Retiro en sede principal por el cliente.',
                'is_active' => true,
            ]
        );

        $customer = Customer::where('email', 'jhosagid77@gmail.com')->first();
        if ($customer) {
            $customer->dispatch_route_id = $routeForanea->id;
            $customer->saveQuietly();
        }
    }
}
