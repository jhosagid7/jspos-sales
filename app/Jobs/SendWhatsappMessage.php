<?php

namespace App\Jobs;

use App\Models\WhatsappMessage;
use App\Services\WhatsappService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsappMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $messageId;

    /**
     * Create a new job instance.
     */
    public function __construct($messageId)
    {
        $this->messageId = $messageId;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsappService $whatsappService): void
    {
        $message = WhatsappMessage::find($this->messageId);
        if (!$message) return;

        // Skip if already sent successfully to prevent duplicates
        if ($message->status === 'sent') return;

        // Ensure service is ready
        if (!$whatsappService->checkStatus()) {
            $message->update([
                'status' => 'failed',
                'error_message' => 'API Node.js no disponible o no autenticada.'
            ]);
            // Re-throw or just return depending on retry configuration. We return to let the outbox resend later.
            return;
        }

        $result = $whatsappService->sendMessage(
            $message->phone_number,
            $message->message_body,
            $message->attachment_path
        );

        if ($result['success']) {
            $message->update([
                'status' => 'sent',
                'error_message' => null
            ]);
        } else {
            $message->update([
                'status' => 'failed',
                'error_message' => substr($result['error'], 0, 500)
            ]);
        }
    }
}
