<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;

class LivewireSignedUploadDomainTest extends TestCase
{
    public function test_livewire_signed_upload_matches_custom_tenant_domain()
    {
        $domain = 'http://jspos-insprocari.test';
        $_SERVER['HTTP_HOST'] = 'jspos-insprocari.test';
        $_SERVER['HTTPS'] = 'off';

        // Re-boot service provider logic for current request context
        Config::set('app.url', $domain);
        URL::forceRootUrl($domain);

        $signedUrl = URL::temporarySignedRoute('livewire.upload-file', now()->addMinutes(5));
        $this->assertStringStartsWith($domain, $signedUrl);

        $parsed = parse_url($signedUrl);
        $pathAndQuery = $parsed['path'] . '?' . ($parsed['query'] ?? '');

        $request = Request::create($domain . $pathAndQuery, 'POST');
        $this->assertTrue($request->hasValidSignature(), 'Signature must be valid when requested on custom tenant domain');
    }
}
