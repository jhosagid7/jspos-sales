<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Services\UpdateService;

class UpdateSystem extends Component
{
    public $currentVersion;
    public $newVersion;
    public $hasUpdate = false;
    public $updateUrl;
    public $releaseBody;
    public $currentReleaseNotes;
    public $status = ''; // idle, checking, backing_up, downloading, updating, done, error

    public function mount(UpdateService $updater)
    {
        $this->currentVersion = $updater->getCurrentVersion();
        $this->currentReleaseNotes = $this->getReleaseNotes($this->currentVersion);
    }

    public function getReleaseNotes($version)
    {
        $path = base_path('CHANGELOG.md');
        if (!file_exists($path)) return '';

        $content = file_get_contents($path);
        $lines = explode("\n", $content);
        $notes = [];
        $capturing = false;

        // Normalize version for search (e.g., v1.3.0 -> [1.3.0])
        $searchVersion = str_replace('v', '', $version);
        $startPattern = "/^## \[" . preg_quote($searchVersion, '/') . "\]/";

        foreach ($lines as $line) {
            // Start capturing when we find the version header
            if (preg_match($startPattern, $line)) {
                $capturing = true;
                continue;
            }

            // Stop capturing when we hit the next version header
            if ($capturing && preg_match('/^## \[/', $line)) {
                break;
            }

            if ($capturing) {
                $notes[] = $line;
            }
        }

        return trim(implode("\n", $notes));
    }

    public function checkUpdate(UpdateService $updater)
    {
        $this->status = 'checking';
        
        $result = $updater->checkUpdate();
        
        if ($result['has_update']) {
            $this->hasUpdate = true;
            $this->newVersion = $result['new_version'];
            $this->updateUrl = $result['url'];
            $this->releaseBody = $result['body'];
            $this->status = 'available';
        } else {
            $this->hasUpdate = false;
            $this->status = 'up_to_date';
        }
    }

    public function update(UpdateService $updater)
    {
        if (!$this->hasUpdate) return;

        $this->status = 'backing_up';
        $this->dispatch('status-changed', status: 'Realizando copia de seguridad...');

        try {
            // Ideally this should be a job or handled step-by-step to show progress
            // For simplicity in Livewire, we might block. 
            // Better: use a Job and poll status. But let's try direct execution for now.
            
            $updater->updateSystem($this->updateUrl);
            
            $this->status = 'done';
            $this->dispatch('noty', msg: 'Sistema actualizado correctamente a la versión ' . $this->newVersion);
            
            // Reload to apply changes
            $this->redirect(request()->header('Referer'));

        } catch (\Exception $e) {
            $this->status = 'error';
            $this->addError('update', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.settings.update-system')
            ->extends('layouts.theme.app')
            ->section('content');
    }
}
