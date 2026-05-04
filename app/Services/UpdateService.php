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
        $maxAttempts = 5; // Increased from 2 to 5 for production stability
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                Log::info("Update download attempt {$attempt} of {$maxAttempts}: {$downloadUrl}");
                
                $response = Http::withHeaders([
                    'User-Agent' => 'JSPOS-Updater'
                ])
                ->withOptions([
                    'curl' => [
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, // Force HTTP/1.1 to avoid common SSL/TLS EOF errors in HTTP/2
                        CURLOPT_SSL_VERIFYPEER => false,               // Temporary fix for SSL certificate issues if any
                        CURLOPT_TCP_KEEPALIVE => 1,                   // Help keep connection alive
                    ]
                ])
                ->timeout(900)          // Increased to 15 minutes
                ->connectTimeout(60)    // Increased to 60 seconds
                ->sink($tempPath)
                ->get($downloadUrl);

                if (!$response->successful()) {
                    throw new \Exception("Download failed with status: " . $response->status());
                }

                Log::info("Update downloaded successfully on attempt {$attempt}.");
                return true;

            } catch (\Exception $e) {
                $lastException = $e;
                Log::warning("Download attempt {$attempt} failed: " . $e->getMessage());
                
                // Clean partial download before retry
                if (File::exists($tempPath)) {
                    File::delete($tempPath);
                }

                if ($attempt < $maxAttempts) {
                    // Exponential backoff
                    $sleepTime = $attempt * 5; 
                    sleep($sleepTime); 
                }
            }
        }

        throw new \Exception("Download failed after {$maxAttempts} attempts: " . $lastException->getMessage());
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
        // Also run permission seeder to ensure new features are accessible
        Artisan::call('db:seed', ['--class' => 'PermissionSeeder', '--force' => true]);

        // Fix: Manual repair for warehouse_id in order_details if they are null
        \App\Models\OrderDetail::whereNull('warehouse_id')->update(['warehouse_id' => 1]);

        // Fix: Create personal_access_tokens table if it doesn't exist (Required for Sanctum API)
        if (!\Illuminate\Support\Facades\Schema::hasTable('personal_access_tokens')) {
            \Illuminate\Support\Facades\Schema::create('personal_access_tokens', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        // Fix: Add sent_at to email_messages if missing (seen in logs)
        if (\Illuminate\Support\Facades\Schema::hasTable('email_messages')) {
            if (!\Illuminate\Support\Facades\Schema::hasColumn('email_messages', 'sent_at')) {
                \Illuminate\Support\Facades\Schema::table('email_messages', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->timestamp('sent_at')->nullable()->after('status');
                });
            }
        }

        return true;
    }

    public function cleanup()
    {
        Artisan::call('optimize:clear');
        \Illuminate\Support\Facades\Cache::forget('system_update_available');
        return true;
    }
}
