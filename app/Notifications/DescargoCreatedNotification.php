<?php

namespace App\Notifications;

use App\Models\Descargo;
use App\Models\Configuration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Barryvdh\DomPDF\Facade\Pdf;

class DescargoCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $descargo;

    /**
     * Create a new notification instance.
     */
    public function __construct(Descargo $descargo)
    {
        $this->descargo = $descargo;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $descargo = $this->descargo->load(['details.product', 'user', 'warehouse']);
        $config = Configuration::first();

        // Generate PDF
        $pdf = Pdf::loadView('reports.descargo-detail-pdf', [
            'adjustment' => $descargo,
            'config' => $config,
            'type' => 'DESCARGO'
        ]);

        return (new MailMessage)
            ->subject('NUEVO DESCARGO REGISTRADO - #' . $descargo->id)
            ->greeting('Hola ' . $notifiable->name)
            ->line('Se ha registrado un nuevo ajuste de salida (Descargo) en el sistema que requiere su revisión.')
            ->line('Detalles del ajuste:')
            ->line('ID: #' . $descargo->id)
            ->line('Depósito: ' . $descargo->warehouse->name)
            ->line('Motivo: ' . $descargo->motive)
            ->line('Autorizado por: ' . $descargo->authorized_by)
            ->line('Responsable: ' . $descargo->user->name)
            ->action('Ver Listado de Descargos', url('/descargos'))
            ->attachData($pdf->output(), 'descargo_' . $descargo->id . '.pdf', [
                'mime' => 'application/pdf',
            ])
            ->line('Gracias por su atención.');
    }
}
