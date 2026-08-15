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
    protected $signature = 'license:generate {client_id} {days=30} {--plan=BASIC} {--add=} {--remove=} {--devices=}';

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
        $removeModules = $this->option('remove') ? explode(',', $this->option('remove')) : [];

        // Leer definiciones desde config/plans.php (fuente única de verdad)
        $tiers = config('plans.tiers', []);
        $dependencies = config('plans.dependencies', []);
        $allAvailable = array_keys(config('plans.available_modules', []));

        // Obtener módulos base del plan seleccionado
        $planKey = strtolower($plan);
        $tier = $tiers[$planKey] ?? $tiers['basic'] ?? ['modules' => [], 'max_devices' => 1];
        
        if ($tier['modules'] === 'all') {
            $modules = $allAvailable;
        } else {
            $modules = $tier['modules'] ?? [];
        }

        // Agregar módulos à la carte
        foreach ($addModules as $mod) {
            $mod = trim($mod);
            if (!empty($mod) && !in_array($mod, $modules)) {
                $modules[] = $mod;
            }
        }

        // Quitar módulos explícitamente removidos
        foreach ($removeModules as $mod) {
            $mod = trim($mod);
            if (!empty($mod)) {
                $modules = array_values(array_filter($modules, fn($m) => $m !== $mod));
            }
        }

        // Auto-resolver dependencias
        $addedDeps = [];
        $changed = true;
        while ($changed) {
            $changed = false;
            foreach ($modules as $mod) {
                if (isset($dependencies[$mod])) {
                    foreach ($dependencies[$mod] as $dep) {
                        if (!in_array($dep, $modules)) {
                            $modules[] = $dep;
                            $addedDeps[] = "$dep (requerido por $mod)";
                            $changed = true;
                        }
                    }
                }
            }
        }

        // Verificar que no se haya removido una dependencia requerida
        foreach ($removeModules as $mod) {
            $mod = trim($mod);
            foreach ($dependencies as $dependent => $deps) {
                if (in_array($mod, $deps) && in_array($dependent, $modules)) {
                    $this->error("Error: No se puede quitar '$mod' porque es requerido por '$dependent'.");
                    return 1;
                }
            }
        }

        // Determinar max_devices (prioridad: flag > plan default)
        $maxDevices = $this->option('devices')
            ? (int) $this->option('devices')
            : ($tier['max_devices'] ?? 1);

        $data = [
            'client_id' => $clientId,
            'expires_at' => $expiresAt,
            'type' => $plan,
            'modules' => array_values(array_unique($modules)),
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
        $this->line("Plan: $plan");
        $this->line("Expires: $expiresAt");
        $this->line("Max Devices: $maxDevices");
        $this->line("Modules (" . count($data['modules']) . "): " . implode(', ', $data['modules']));

        if (!empty($addedDeps)) {
            $this->newLine();
            $this->warn("Dependencias agregadas automáticamente:");
            foreach ($addedDeps as $dep) {
                $this->line("  → $dep");
            }
        }

        $this->newLine();
        $this->comment($finalKey);
        $this->newLine();

        return 0;
    }
}
