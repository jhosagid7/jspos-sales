<?php

namespace App\Notifications;

use App\Models\Cargo;
use App\Models\WhatsappTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CargoCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $cargo;

    /**
     * Create a new notification instance.
     */
    public function __construct(Cargo $cargo)
    {
        $this->cargo = $cargo;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $template = WhatsappTemplate::where('event_type', 'cargo_created')->first();
        if ($template && $template->is_active) {
            return ['mail'];
        }
        return [];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $template = WhatsappTemplate::where('event_type', 'cargo_created')->first();
        $conf = \App\Models\Configuration::first();
        
        $body = $template->body ?? 'Se ha registrado un nuevo Cargo #[CARGO_ID] por el motivo: [MOTIVO].';
        $subject = $template->subject ?? 'Nuevo Cargo / Ajuste Creado';

        // Compile variables
        $vars = [
            '[CARGO_ID]' => $this->cargo->id,
            '[MOTIVO]' => $this->cargo->motive,
            '[USUARIO]' => $this->cargo->user->name,
            '[FECHA]' => $this->cargo->date->format('d/m/Y H:i'),
            '[EMPRESA]' => $conf->business_name ?? 'Sistema POS'
        ];

        $messageText = str_replace(array_keys($vars), array_values($vars), $body);

        return (new MailMessage)
                    ->subject($subject)
                    ->greeting('Hola ' . $notifiable->name)
                    ->line($messageText)
                    ->action('Ver Cargo en el Panel', url('/cargos'))
                    ->line('Gracias por usar nuestro sistema.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'cargo_id' => $this->cargo->id,
            'motive' => $this->cargo->motive
        ];
    }
}
