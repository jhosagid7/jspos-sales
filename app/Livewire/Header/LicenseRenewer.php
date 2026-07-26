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
    public $clientId;
    public $licenseType;
    public $businessName;
    public $clientName;
    public $showModal = false;

    protected $listeners = ['trigger-license-modal' => 'openModal'];

    public function mount($daysRemaining = null)
    {
        if ($daysRemaining === null) {
            $service = app(LicenseService::class);
            $status = $service->checkLicense();
            $this->daysRemaining = $status['days_remaining'] ?? 0;
            $this->clientId = $service->getClientId();
            $this->licenseType = $status['type'] ?? 'NO ACTIVA';
            $this->businessName = Configuration::first()->business_name ?? 'Empresa Genérica';
            
            $latestLicense = \App\Models\License::latest('id')->first();
            $this->clientName = $latestLicense ? $latestLicense->client_name : '';
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
                $this->licenseType = $status['type'] ?? 'NO ACTIVA';
                
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
        $email = $config->license_request_email;
        $phone = $config->license_request_phone;

        if (!$email && !$phone) {
            $this->dispatch('noty', msg: 'No hay correo ni teléfono configurado para solicitudes. Contacte a soporte.');
            return;
        }

        try {
            $clientId = app(LicenseService::class)->getClientId();
            $businessName = $config->business_name ?? 'Sin nombre';
            $clientName = '';
            
            // Check if we have a client_name from latest license
            $latestLicense = \App\Models\License::latest('id')->first();
            if ($latestLicense && $latestLicense->client_name) {
                $clientName = " ({$latestLicense->client_name})";
            }

            $subject = "Solicitud de Renovación de Licencia - " . $businessName . $clientName;
            $body = "El cliente '*{$businessName}*'$clientName (ID: $clientId) ha solicitado una renovación de licencia.\n\n" .
                    "Por favor contacte al cliente para gestionar la renovación.";

            // Send email if configured
            if ($email) {
                try {
                    Mail::raw($body, function ($message) use ($email, $subject) {
                        $message->to($email)
                                ->subject($subject);
                    });
                } catch (\Exception $e) {
                    Log::error("License Request Email Error: " . $e->getMessage());
                }
            }

            // Send via internal WhatsApp API if configured
            if ($phone) {
                try {
                    $whatsappService = app(\App\Services\WhatsappService::class);
                    $whatsappService->sendMessage($phone, $body);
                } catch (\Exception $e) {
                    Log::error("WhatsApp Notification Error: " . $e->getMessage());
                }
            }

            $this->dispatch('hide-license-modal');
            $this->dispatch('noty', msg: 'Solicitud enviada correctamente.');

        } catch (\Exception $e) {
            Log::error("License Request Error: " . $e->getMessage());
            $this->dispatch('noty', msg: 'Error al enviar la solicitud. Verifique su conexión.');
        }
    }
}
