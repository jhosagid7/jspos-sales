<?php

namespace Tests\Feature;

use App\Services\UpdateService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UpdateServiceTest extends TestCase
{
    public function test_check_update_uses_raw_cdn_fallback_when_api_rate_limited()
    {
        // Mock GitHub API returning 403 Rate Limit
        Http::fake([
            'https://api.github.com/repos/jhosagid7/jspos-sales/releases/latest' => Http::response([
                'message' => 'API rate limit exceeded for 127.0.0.1'
            ], 403),
            'https://api.github.com/repos/jhosagid7/jspos-sales/tags' => Http::response([
                'message' => 'API rate limit exceeded for 127.0.0.1'
            ], 403),
            'https://raw.githubusercontent.com/jhosagid7/jspos-sales/develop/version.txt' => Http::response('9.99.999', 200),
            'https://raw.githubusercontent.com/jhosagid7/jspos-sales/develop/CHANGELOG.md' => Http::response("## [9.99.999] - 2026-08-15\n\n### Fixed\n- Fixes", 200),
        ]);

        $service = new UpdateService();
        $result = $service->checkUpdate();

        $this->assertTrue($result['has_update']);
        $this->assertEquals('v9.99.999', $result['new_version']);
    }

    public function test_system_current_version_endpoint_returns_json()
    {
        $user = \App\Models\User::factory()->create();
        $response = $this->actingAs($user)->getJson('/system/current-version');

        $response->assertStatus(200);
        $response->assertJsonStructure(['version']);
        $expectedVer = trim(file_get_contents(base_path('version.txt')));
        $this->assertEquals($expectedVer, $response->json('version'));
    }

    public function test_update_system_blade_does_not_contain_unsafe_named_route()
    {
        $bladePath = resource_path('views/livewire/settings/update-system.blade.php');
        $content = file_get_contents($bladePath);

        // Ensures we never throw RouteNotFoundException in blade
        $this->assertStringNotContainsString("route('system.current.version')", $content);
        $this->assertStringNotContainsString('route("system.current.version")', $content);
        $this->assertStringContainsString('url("/system/current-version")', $content);
    }
}

