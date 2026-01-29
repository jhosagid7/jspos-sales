<?php

namespace App\Livewire\Header;

use Livewire\Component;
use App\Services\LicenseService;
use App\Models\Configuration;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class LicenseRenewer extends Component
{
    public $daysRemaining;
    public $licenseKey;
    public $showModal = false;

    protected $listeners = ['trigger-license-modal' => 'openModal'];

    public function mount($daysRemaining = null)
    {
        if ($daysRemaining === null) {
            $service = app(LicenseService::class);
            $status = $service->checkLicense();
            $this->daysRemaining = $status['days_remaining'] ?? 0;
        } else {
            $this->daysRemaining = $daysRemaining;
        }
    }

    public function render()
    {
        return view('livewire.header.license-renewer');
    }

    public function openModal()
    {
        $this->resetErrorBag();
        $this->licenseKey = '';
        $this->dispatch('show-license-modal'); 
    }

    public function renew()
    {
        $this->validate([
            'licenseKey' => 'required|string',
        ]);

        try {
            $service = app(LicenseService::class);
            $success = $service->activateLicense($this->licenseKey);

            if ($success) {
                // Determine new days remaining
                $status = $service->checkLicense();
                $this->daysRemaining = $status['days_remaining'];
                
                $this->dispatch('hide-license-modal');
                $this->dispatch('noty', msg: 'Licencia activada con éxito.');
                
                // Optional: Emit event to refresh other components if needed
                // $this->dispatch('licenseUpdated'); 
                
                // Reload page to clear any middleware blocks or update global state
                $this->dispatch('reload-page');

            } else {
                $this->addError('licenseKey', 'La licencia es inválida, expirada o corresponde a otro cliente.');
            }
        } catch (\Exception $e) {
            Log::error("License Renewal Error: " . $e->getMessage());
            $this->addError('licenseKey', 'Error interno al validar la licencia.');
        }
    }

    public function requestRenewal()
    {
        $config = Configuration::first();
        $recipient = $config->license_request_email;

        if (!$recipient) {
            $this->dispatch('noty', msg: 'No hay correo configurado para solicitudes. Contacte a soporte.');
            return;
        }

        try {
            $clientId = app(LicenseService::class)->getClientId();
            $businessName = $config->business_name ?? 'Sin nombre';

            $subject = "Solicitud de Renovación de Licencia - " . $businessName;
            $body = "El cliente '$businessName' (ID: $clientId) ha solicitado una renovación de licencia.\n\n" .
                    "Por favor contacte al cliente para gestionar la renovación.";

            // Using raw mail for simplicity as requested, ensuring mail config is set in .env
            Mail::raw($body, function ($message) use ($recipient, $subject) {
                $message->to($recipient)
                        ->subject($subject);
            });

            $this->dispatch('hide-license-modal');
            $this->dispatch('noty', msg: 'Solicitud enviada correctamente.');

        } catch (\Exception $e) {
            Log::error("License Request Email Error: " . $e->getMessage());
            $this->dispatch('noty', msg: 'Error al enviar la solicitud. Verifique su conexión.');
        }
    }
}
