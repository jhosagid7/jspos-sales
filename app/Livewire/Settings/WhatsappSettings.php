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

    public function mount()
    {
        // Load or create defaults
        $saleTemplate = WhatsappTemplate::firstOrCreate(
            ['event_type' => 'sale_created'],
            [
                'subject' => $this->sale_subject,
                'body' => $this->sale_body,
                'is_active' => true
            ]
        );
        $this->sale_active = $saleTemplate->is_active;
        $this->sale_subject = $saleTemplate->subject;
        $this->sale_body = $saleTemplate->body;

        $paymentTemplate = WhatsappTemplate::firstOrCreate(
            ['event_type' => 'payment_received'],
            [
                'subject' => $this->payment_subject,
                'body' => $this->payment_body,
                'is_active' => true
            ]
        );
        $this->payment_active = $paymentTemplate->is_active;
        $this->payment_subject = $paymentTemplate->subject;
        $this->payment_body = $paymentTemplate->body;

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

        $this->dispatch('noty', msg: 'CONFIGURACIÓN DE WHATSAPP GUARDADA');
    }

    public function render()
    {
        return view('livewire.settings.whatsapp-settings');
    }
}
