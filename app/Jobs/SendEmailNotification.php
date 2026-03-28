<?php

namespace App\Jobs;

use App\Models\EmailMessage;
use App\Mail\GenericNotificationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Exception;

class SendEmailNotification implements ShouldQueue
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
    public function handle(): void
    {
        $message = EmailMessage::find($this->messageId);
        if (!$message) return;

        if ($message->status === 'sent') return;

        try {
            Mail::to($message->email_address)->send(new GenericNotificationMail(
                $message->subject,
                $message->message_body,
                $message->attachment_path
            ));

            $message->update([
                'status' => 'sent',
                'sent_at' => now(),
                'error_message' => null
            ]);
        } catch (Exception $e) {
            $message->update([
                'status' => 'failed',
                'error_message' => substr($e->getMessage(), 0, 500)
            ]);
        }
    }
}
