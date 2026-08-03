<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use App\Services\LicenseService;

class LicenseAutoConnectTest extends TestCase
{
    public function test_ping_endpoint_returns_online_status()
    {
        $response = $this->getJson('/api/license/ping');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'client_id',
                'has_license',
                'installed',
            ])
            ->assertJson([
                'status' => 'online',
            ]);
    }

    public function test_installer_connect_to_license_server_registers_successfully()
    {
        Http::fake([
            'http://100.115.248.9/api/clients/register' => Http::response([
                'status' => 'success',
                'registered' => true,
                'has_license' => false,
                'message' => 'Cliente registrado en línea.',
            ], 200),
        ]);

        $response = $this->postJson(route('install.connectLicenseServer'), [
            'server_ip' => '100.115.248.9',
            'client_name' => 'Tienda Auto Test',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'registered',
            ]);
    }
}
