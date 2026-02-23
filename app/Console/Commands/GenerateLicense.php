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
    protected $signature = 'license:generate {client_id} {days=30} {--plan=BASIC} {--add=} {--devices=1}';

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

        $plan = strtoupper($this->option('plan'));
        $addModules = $this->option('add') ? explode(',', $this->option('add')) : [];

        // Define Module Tiers
        $basicModules = [
            // El núcleo no se puede apagar, estos son extras.
        ];
        $proModules = array_merge($basicModules, [
            'module_credits', 'module_purchases', 'module_advanced_payments', 
            'module_multi_warehouse', 'module_advanced_products', 'module_labels',
            'module_advanced_reports', 'module_roles'
        ]);
        $premiumModules = array_merge($proModules, [
            'module_whatsapp', 'module_commissions', 'module_production', 'module_delivery',
            'module_updates', 'module_backups'
        ]);

        $modules = [];
        if ($plan === 'PREMIUM') $modules = $premiumModules;
        elseif ($plan === 'PRO') $modules = $proModules;
        else $modules = $basicModules;

        // Add á la carte modules
        foreach ($addModules as $mod) {
            $mod = trim($mod);
            if (!empty($mod) && !in_array($mod, $modules)) {
                $modules[] = $mod;
            }
        }

        // Dependency Validation
        if (in_array('module_production', $modules) && !in_array('module_multi_warehouse', $modules)) {
            $this->error("Error: module_production requiere module_multi_warehouse para funcionar.");
            return 1;
        }

        $maxDevices = (int) $this->option('devices');

        $data = [
            'client_id' => $clientId,
            'expires_at' => $expiresAt,
            'type' => $plan,
            'modules' => $modules,
            'max_devices' => $maxDevices
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
