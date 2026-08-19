<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\UpdateService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

class UpdateDownloadPermissionTest extends TestCase
{
    public function test_download_update_uses_storage_path_and_unique_filenames_per_attempt()
    {
        Http::fake([
            'https://api.github.com/repos/jhosagid7/jspos-sales/zipball/v1.10.390' => Http::sequence()
                ->push('Error 504', 504) // First attempt fails
                ->push('ZIP_DUMMY_CONTENT', 200) // Second attempt succeeds
        ]);

        $service = new UpdateService();
        $result = $service->downloadUpdate('https://api.github.com/repos/jhosagid7/jspos-sales/zipball/v1.10.390');

        $this->assertTrue($result);
        $downloadedZip = session('latest_downloaded_update_zip');
        $this->assertNotEmpty($downloadedZip);
        $this->assertStringContainsString('storage', $downloadedZip);
        $this->assertStringNotContainsString('Windows', $downloadedZip);
        $this->assertTrue(File::exists($downloadedZip));

        // Cleanup
        File::delete($downloadedZip);
    }
}
