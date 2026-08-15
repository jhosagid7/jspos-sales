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
            'https://raw.githubusercontent.com/jhosagid7/jspos-sales/develop/version.txt' => Http::response('1.10.385', 200),
            'https://raw.githubusercontent.com/jhosagid7/jspos-sales/develop/CHANGELOG.md' => Http::response("## [1.10.385] - 2026-08-15\n\n### Fixed\n- Fixes", 200),
        ]);

        $service = new UpdateService();
        $result = $service->checkUpdate();

        $this->assertTrue($result['has_update']);
        $this->assertEquals('v1.10.385', $result['new_version']);
    }
}
