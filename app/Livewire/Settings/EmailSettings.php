<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Models\EmailTemplate;

class EmailSettings extends Component
{
    public $sale_active = true;
    public $sale_subject = 'Notificación de Venta';
    public $sale_body = 'Hola [CLIENTE], adjunto a este mensaje encontrarás el recibo de tu compra #[FACTURA] por un total de [TOTAL]. ¡Gracias por tu preferencia!';
    public $sale_dispatch_mode = 'auto';

    public $payment_active = true;
    public $payment_subject = 'Notificación de Abono';
    public $payment_body = 'Hola [CLIENTE], hemos recibido tu pago por [MONTO_PAGADO] a la factura #[FACTURA_PAGADA]. Tu saldo restante es de [SALDO_RESTANTE].';
    public $payment_dispatch_mode = 'auto';

    public $cargo_active = true;
    public $cargo_subject = 'Nuevo Cargo / Ajuste Creado';
    public $cargo_body = 'Hola, se ha registrado un nuevo Cargo #[CARGO_ID] por el motivo: [MOTIVO]. Responsable: [USUARIO]. Por favor revisa el panel para su aprobación.';
    public $cargo_dispatch_mode = 'auto';

    public $descargo_active = true;
    public $descargo_subject = 'Nuevo Descargo / Salida de Inventario';
    public $descargo_body = 'Hola, se ha registrado una nueva Salida #[DESCARGO_ID] por el motivo: [MOTIVO]. Responsable: [USUARIO]. Por favor revisa el panel para su aprobación.';
    public $descargo_dispatch_mode = 'auto';

    public function mount()
    {
        // ... (existing sale/payment load) ...
        $saleTemplate = EmailTemplate::firstOrCreate(
            ['event_type' => 'sale_created'],
            ['subject' => $this->sale_subject, 'body' => $this->sale_body, 'is_active' => true, 'dispatch_mode' => 'auto']
        );
        $this->sale_active = $saleTemplate->is_active;
        $this->sale_subject = $saleTemplate->subject;
        $this->sale_body = $saleTemplate->body;
        $this->sale_dispatch_mode = $saleTemplate->dispatch_mode ?? 'auto';

        $paymentTemplate = EmailTemplate::firstOrCreate(
            ['event_type' => 'payment_received'],
            ['subject' => $this->payment_subject, 'body' => $this->payment_body, 'is_active' => true, 'dispatch_mode' => 'auto']
        );
        $this->payment_active = $paymentTemplate->is_active;
        $this->payment_subject = $paymentTemplate->subject;
        $this->payment_body = $paymentTemplate->body;
        $this->payment_dispatch_mode = $paymentTemplate->dispatch_mode ?? 'auto';

        $cargoTemplate = EmailTemplate::firstOrCreate(
            ['event_type' => 'cargo_created'],
            [
                'subject' => $this->cargo_subject,
                'body' => $this->cargo_body,
                'is_active' => true,
                'dispatch_mode' => 'auto'
            ]
        );
        $this->cargo_active = $cargoTemplate->is_active;
        $this->cargo_subject = $cargoTemplate->subject;
        $this->cargo_body = $cargoTemplate->body;
        $this->cargo_dispatch_mode = $cargoTemplate->dispatch_mode ?? 'auto';

        $descargoTemplate = EmailTemplate::firstOrCreate(
            ['event_type' => 'descargo_created'],
            [
                'subject' => $this->descargo_subject,
                'body' => $this->descargo_body,
                'is_active' => true,
                'dispatch_mode' => 'auto'
            ]
        );
        $this->descargo_active = $descargoTemplate->is_active;
        $this->descargo_subject = $descargoTemplate->subject;
        $this->descargo_body = $descargoTemplate->body;
        $this->descargo_dispatch_mode = $descargoTemplate->dispatch_mode ?? 'auto';

        session(['map' => 'Ajustes', 'child' => ' Correo Electrónico']);
    }

    public function save()
    {
        EmailTemplate::updateOrCreate(
            ['event_type' => 'sale_created'],
            [
                'subject' => $this->sale_subject,
                'body' => $this->sale_body,
                'is_active' => $this->sale_active,
                'dispatch_mode' => $this->sale_dispatch_mode
            ]
        );

        EmailTemplate::updateOrCreate(
            ['event_type' => 'payment_received'],
            [
                'subject' => $this->payment_subject,
                'body' => $this->payment_body,
                'is_active' => $this->payment_active,
                'dispatch_mode' => $this->payment_dispatch_mode
            ]
        );

        EmailTemplate::updateOrCreate(
            ['event_type' => 'cargo_created'],
            [
                'subject' => $this->cargo_subject,
                'body' => $this->cargo_body,
                'is_active' => $this->cargo_active,
                'dispatch_mode' => $this->cargo_dispatch_mode
            ]
        );

        EmailTemplate::updateOrCreate(
            ['event_type' => 'descargo_created'],
            [
                'subject' => $this->descargo_subject,
                'body' => $this->descargo_body,
                'is_active' => $this->descargo_active,
                'dispatch_mode' => $this->descargo_dispatch_mode
            ]
        );

        $this->dispatch('noty', msg: 'CONFIGURACIÓN DE CORREO GUARDADA');
    }

    public function render()
    {
        return view('livewire.settings.email-settings');
    }
}
