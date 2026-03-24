<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Models\WhatsappTemplate;

class WhatsappSettings extends Component
{
    public $sale_active = true;
    public $sale_subject = 'Notificación de Venta';
    public $sale_body = 'Hola [CLIENTE], adjunto a este mensaje encontrarás el recibo de tu compra #[FACTURA] por un total de [TOTAL]. ¡Gracias por tu preferencia!';

    public $payment_active = true;
    public $payment_subject = 'Notificación de Abono';
    public $payment_body = 'Hola [CLIENTE], hemos recibido tu pago por [MONTO_PAGADO] a la factura #[FACTURA_PAGADA]. Tu saldo restante es de [SALDO_RESTANTE].';

    public $cargo_active = true;
    public $cargo_subject = 'Nuevo Cargo / Ajuste Creado';
    public $cargo_body = 'Hola, se ha registrado un nuevo Cargo #[CARGO_ID] por el motivo: [MOTIVO]. Responsable: [USUARIO]. Por favor revisa el panel para su aprobación.';

    public $descargo_active = true;
    public $descargo_subject = 'Nuevo Descargo / Salida de Inventario';
    public $descargo_body = 'Hola, se ha registrado una nueva Salida #[DESCARGO_ID] por el motivo: [MOTIVO]. Responsable: [USUARIO]. Por favor revisa el panel para su aprobación.';

    public function mount()
    {
        // ... (existing sale/payment load) ...
        $saleTemplate = WhatsappTemplate::firstOrCreate(
            ['event_type' => 'sale_created'],
            ['subject' => $this->sale_subject, 'body' => $this->sale_body, 'is_active' => true]
        );
        $this->sale_active = $saleTemplate->is_active;
        $this->sale_subject = $saleTemplate->subject;
        $this->sale_body = $saleTemplate->body;

        $paymentTemplate = WhatsappTemplate::firstOrCreate(
            ['event_type' => 'payment_received'],
            ['subject' => $this->payment_subject, 'body' => $this->payment_body, 'is_active' => true]
        );
        $this->payment_active = $paymentTemplate->is_active;
        $this->payment_subject = $paymentTemplate->subject;
        $this->payment_body = $paymentTemplate->body;

        $cargoTemplate = WhatsappTemplate::firstOrCreate(
            ['event_type' => 'cargo_created'],
            [
                'subject' => $this->cargo_subject,
                'body' => $this->cargo_body,
                'is_active' => true
            ]
        );
        $this->cargo_active = $cargoTemplate->is_active;
        $this->cargo_subject = $cargoTemplate->subject;
        $this->cargo_body = $cargoTemplate->body;

        $descargoTemplate = WhatsappTemplate::firstOrCreate(
            ['event_type' => 'descargo_created'],
            [
                'subject' => $this->descargo_subject,
                'body' => $this->descargo_body,
                'is_active' => true
            ]
        );
        $this->descargo_active = $descargoTemplate->is_active;
        $this->descargo_subject = $descargoTemplate->subject;
        $this->descargo_body = $descargoTemplate->body;

        session(['map' => 'Ajustes', 'child' => ' WhatsApp']);
    }

    public function disconnectWhatsapp()
    {
        try {
            $response = \Illuminate\Support\Facades\Http::post('http://localhost:3000/logout');
            
            if ($response->successful()) {
                $this->dispatch('noty', msg: 'SESIÓN DE WHATSAPP DESCONECTADA');
            } else {
                $this->dispatch('msg-error', msg: 'Hubo un problema al intentar desconectar.');
            }
        } catch (\Exception $e) {
            $this->dispatch('msg-error', msg: 'No se pudo conectar con el servicio de WhatsApp.');
        }
    }

    public function save()
    {
        WhatsappTemplate::updateOrCreate(
            ['event_type' => 'sale_created'],
            [
                'subject' => $this->sale_subject,
                'body' => $this->sale_body,
                'is_active' => $this->sale_active
            ]
        );

        WhatsappTemplate::updateOrCreate(
            ['event_type' => 'payment_received'],
            [
                'subject' => $this->payment_subject,
                'body' => $this->payment_body,
                'is_active' => $this->payment_active
            ]
        );

        WhatsappTemplate::updateOrCreate(
            ['event_type' => 'cargo_created'],
            [
                'subject' => $this->cargo_subject,
                'body' => $this->cargo_body,
                'is_active' => $this->cargo_active
            ]
        );

        WhatsappTemplate::updateOrCreate(
            ['event_type' => 'descargo_created'],
            [
                'subject' => $this->descargo_subject,
                'body' => $this->descargo_body,
                'is_active' => $this->descargo_active
            ]
        );

        $this->dispatch('noty', msg: 'CONFIGURACIÓN DE WHATSAPP GUARDADA');
    }

    public function render()
    {
        return view('livewire.settings.whatsapp-settings');
    }
}
