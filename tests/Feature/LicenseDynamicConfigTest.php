<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use App\Services\LicenseService;

class LicenseDynamicConfigTest extends TestCase
{
    public function test_expired_license_view_does_not_contain_hardcoded_legacy_ip()
    {
        $response = $this->get('/license/expired');

        $response->assertStatus(200);
        $response->assertDontSee('100.74.104.82');
    }

    public function test_sync_retains_typed_server_ip_on_failure()
    {
        Http::fake([
            'http://100.220.10.5:9000/api/clients/check-status' => Http::response([], 500),
        ]);

        $response = $this->post(route('license.sync'), [
            'server_ip' => '100.220.10.5:9000',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasInput('server_ip', '100.220.10.5:9000');
    }

    public function test_sync_saves_server_ip_on_successful_license_check()
    {
        $licenseService = app(LicenseService::class);
        $clientId = $licenseService->getClientId();

        Http::fake([
            'http://100.220.10.5:9000/api/clients/check-status' => Http::response([
                'has_license' => false,
                'message' => 'No active license assigned yet.',
            ], 200),
        ]);

        $response = $this->post(route('license.sync'), [
            'server_ip' => '100.220.10.5:9000',
        ]);

        $response->assertRedirect();
        $this->assertEquals('100.220.10.5:9000', session('license_server_ip'));
    }
}
