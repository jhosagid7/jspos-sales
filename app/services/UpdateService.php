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
        $path = base_path('version.txt');
        if (File::exists($path)) {
            return trim(File::get($path));
        }
        return 'v1.0.0';
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

    public function runBackup()
    {
        try {
            Artisan::call('backup:run', ['--only-db' => true]);
            return true;
        } catch (\Exception $e) {
            throw new \Exception("Backup failed: " . $e->getMessage());
        }
    }

    public function downloadUpdate($downloadUrl)
    {
        $tempPath = storage_path('app/temp_update.zip');
        
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'JSPOS-Updater'
            ])->sink($tempPath)->get($downloadUrl);

            if (!$response->successful()) {
                throw new \Exception("Download failed.");
            }
            return true;
        } catch (\Exception $e) {
            throw new \Exception("Download failed: " . $e->getMessage());
        }
    }

    public function installUpdate($newVersion = null)
    {
        $tempPath = storage_path('app/temp_update.zip');
        $zip = new ZipArchive;
        
        if ($zip->open($tempPath) === TRUE) {
            $extractPath = storage_path('app/temp_extract');
            File::makeDirectory($extractPath, 0755, true, true);
            
            $zip->extractTo($extractPath);
            $zip->close();

            $files = File::directories($extractPath);
            if (count($files) > 0) {
                $source = $files[0];
                File::copyDirectory($source, base_path());
            }

            // Explicitly update version.txt
            if ($newVersion) {
                File::put(base_path('version.txt'), $newVersion);
            }

            File::deleteDirectory($extractPath);
            File::delete($tempPath);
            return true;
        } else {
            throw new \Exception("Failed to unzip update.");
        }
    }

    public function runMigrations()
    {
        Artisan::call('migrate', ['--force' => true]);
        return true;
    }

    public function cleanup()
    {
        Artisan::call('optimize:clear');
        return true;
    }
}
