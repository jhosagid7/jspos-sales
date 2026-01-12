<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class UpdateService
{
    protected $owner;
    protected $repo;
    protected $currentVersion;

    public function __construct()
    {
        $this->owner = env('GITHUB_REPO_OWNER', 'jhosagid7');
        $this->repo = env('GITHUB_REPO_NAME', 'jspos-sales');
        // Get current version from CHANGELOG or config. For now assuming config or hardcoded for dev.
        // Ideally we parse CHANGELOG.md or have a version file.
        // Let's assume the user will define APP_VERSION in .env or we parse it.
        // For now, we'll fetch the latest tag from git if available, or use a fallback.
    }

    public function getCurrentVersion()
    {
        // Try to get from git tag
        try {
            $version = trim(shell_exec('git describe --tags --abbrev=0'));
            if ($version) return $version;
        } catch (\Exception $e) {}

        return 'v1.3.0'; // Fallback
    }

    public function checkUpdate()
    {
        $url = "https://api.github.com/repos/{$this->owner}/{$this->repo}/releases/latest";
        
        try {
            $response = Http::get($url);
            
            if ($response->successful()) {
                $data = $response->json();
                $latestVersion = $data['tag_name'];
                $currentVersion = $this->getCurrentVersion();

                // Simple string comparison or version_compare
                // Remove 'v' prefix if present for comparison
                $v1 = ltrim($latestVersion, 'v');
                $v2 = ltrim($currentVersion, 'v');

                if (version_compare($v1, $v2, '>')) {
                    return [
                        'new_version' => $latestVersion,
                        'current_version' => $currentVersion,
                        'url' => $data['zipball_url'], // or assets
                        'body' => $data['body'],
                        'has_update' => true
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error("Update check failed: " . $e->getMessage());
        }

        return [
            'has_update' => false,
            'current_version' => $this->getCurrentVersion()
        ];
    }

    public function updateSystem($downloadUrl)
    {
        // 1. Backup Database
        try {
            Artisan::call('backup:run', ['--only-db' => true]);
        } catch (\Exception $e) {
            throw new \Exception("Backup failed: " . $e->getMessage());
        }

        // 2. Download Update
        $tempPath = storage_path('app/temp_update.zip');
        
        // GitHub requires User-Agent
        $response = Http::withHeaders([
            'User-Agent' => 'JSPOS-Updater'
        ])->sink($tempPath)->get($downloadUrl);

        if (!$response->successful()) {
            throw new \Exception("Download failed.");
        }

        // 3. Unzip and Install
        $zip = new ZipArchive;
        if ($zip->open($tempPath) === TRUE) {
            // GitHub zips usually have a root folder like 'user-repo-hash'. 
            // We need to extract content OF that folder to root.
            
            $extractPath = storage_path('app/temp_extract');
            File::makeDirectory($extractPath, 0755, true, true);
            
            $zip->extractTo($extractPath);
            $zip->close();

            // Find the root folder inside extraction
            $files = File::directories($extractPath);
            if (count($files) > 0) {
                $source = $files[0]; // This is the 'user-repo-hash' folder
                
                // Move files to base_path
                File::copyDirectory($source, base_path());
            }

            // Cleanup
            File::deleteDirectory($extractPath);
            File::delete($tempPath);
        } else {
            throw new \Exception("Failed to unzip update.");
        }

        // 4. Run Migrations
        Artisan::call('migrate', ['--force' => true]);
        
        // 5. Clear Cache
        Artisan::call('optimize:clear');

        return true;
    }
}
