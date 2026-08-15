<?php

namespace App\Livewire\Settings;

use Livewire\Component;

class LicenseGenerator extends Component
{
    public $clientId;
    public $days = 30;
    public $maxDevices = 1;
    public $selectedModules = [];
    public $selectedPlan = 'BÁSICO';
    public $generatedKey = '';

    /**
     * Módulos disponibles: se leen desde config/plans.php
     */
    public function getAvailableModulesProperty()
    {
        return config('plans.available_modules', []);
    }

    /**
     * Dependencias: se leen desde config/plans.php
     */
    public function getDependenciesProperty()
    {
        return config('plans.dependencies', []);
    }

    public function setPreset($plan)
    {
        $tiers = config('plans.tiers', []);
        $allModules = array_keys(config('plans.available_modules', []));

        if ($plan === 'PRO') {
            $tier = $tiers['pro'] ?? [];
            $this->selectedModules = $tier['modules'] ?? [];
            $this->maxDevices = $tier['max_devices'] ?? 5;
            $this->selectedPlan = 'PRO';
        } elseif ($plan === 'PREMIUM') {
            $tier = $tiers['premium'] ?? [];
            $this->selectedModules = ($tier['modules'] === 'all') ? $allModules : ($tier['modules'] ?? $allModules);
            $this->maxDevices = $tier['max_devices'] ?? 999;
            $this->selectedPlan = 'PREMIUM';
        } else {
            $tier = $tiers['basic'] ?? [];
            $this->selectedModules = $tier['modules'] ?? [];
            $this->maxDevices = $tier['max_devices'] ?? 1;
            $this->selectedPlan = 'BÁSICO';
        }
    }

    /**
     * Al seleccionar/deseleccionar un módulo, auto-resolver dependencias.
     */
    public function updatedSelectedModules($value)
    {
        $dependencies = config('plans.dependencies', []);
        
        // Auto-agregar dependencias para cada módulo seleccionado
        $changed = true;
        while ($changed) {
            $changed = false;
            foreach ($this->selectedModules as $mod) {
                if (isset($dependencies[$mod])) {
                    foreach ($dependencies[$mod] as $dep) {
                        if (!in_array($dep, $this->selectedModules)) {
                            $this->selectedModules[] = $dep;
                            $changed = true;
                            
                            $depLabel = config("plans.available_modules.$dep", $dep);
                            $modLabel = config("plans.available_modules.$mod", $mod);
                            $this->dispatch('noty', msg: "Dependencia agregada: $depLabel (requerido por $modLabel)");
                        }
                    }
                }
            }
        }
    }

    public function generate()
    {
        $this->validate([
            'clientId' => 'required|string|min:5',
            'days' => 'required|numeric|min:1',
            'maxDevices' => 'required|numeric|min:1'
        ]);

        $add = implode(',', $this->selectedModules);

        // Mapear el plan seleccionado al formato del comando
        $planMap = ['BÁSICO' => 'BASIC', 'PRO' => 'PRO', 'PREMIUM' => 'PREMIUM'];
        $planCmd = $planMap[$this->selectedPlan] ?? 'BASIC';

        // Run the artisan command silently and grab output
        $exitCode = \Illuminate\Support\Facades\Artisan::call('license:generate', [
            'client_id' => $this->clientId,
            'days' => $this->days,
            '--plan' => $planCmd,
            '--add' => $add,
            '--devices' => $this->maxDevices
        ]);

        $output = \Illuminate\Support\Facades\Artisan::output();
        
        if ($exitCode === 0) {
            $lines = explode("\n", trim($output));
            $this->generatedKey = end($lines);
            $this->dispatch('noty', msg: 'Licencia SaaS generada exitosamente');
        } else {
            $this->dispatch('msg-error', msg: 'Error de jerarquía. Revisa la consola o las dependencias.');
            $this->generatedKey = "ERROR: \n" . $output;
        }
    }

    public function render()
    {
        return view('livewire.settings.license-generator')
            ->extends('layouts.theme.app')
            ->section('content');
    }
}
