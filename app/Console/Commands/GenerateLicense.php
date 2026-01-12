<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateLicense extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'license:generate {client_id} {days=30}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a signed license key for a client';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $clientId = $this->argument('client_id');
        $days = (int) $this->argument('days');
        $expiresAt = now()->addDays($days)->toIso8601String();

        $data = [
            'client_id' => $clientId,
            'expires_at' => $expiresAt,
            'type' => 'standard'
        ];

        $jsonData = json_encode($data);

        // Load Private Key
        $privateKeyPath = base_path('private_key.pem');
        if (!file_exists($privateKeyPath)) {
            $this->error("Private key not found at: $privateKeyPath");
            return 1;
        }

        $privateKey = file_get_contents($privateKeyPath);
        
        // Sign Data
        $signature = '';
        $success = openssl_sign($jsonData, $signature, $privateKey, OPENSSL_ALGO_SHA512);

        if (!$success) {
            $this->error("Failed to sign license data.");
            return 1;
        }

        // Combine Data and Signature
        $licenseString = $jsonData . "||" . base64_encode($signature);
        $finalKey = base64_encode($licenseString);

        $this->info("License generated successfully!");
        $this->line("Client ID: $clientId");
        $this->line("Expires: $expiresAt");
        $this->newLine();
        $this->comment($finalKey);
        $this->newLine();

        return 0;
    }
}
