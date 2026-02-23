<?php

namespace App\Console\Commands;

use App\Models\WhatsappMessage;
use App\Jobs\SendWhatsappMessage;
use Illuminate\Console\Command;

class ProcessWhatsappOutbox extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:process-outbox';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending and failed WhatsApp messages from the outbox';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting WhatsApp Outbox Processor...');

        // Get pending or failed messages. You could add logic to limit retries if needed.
        $messages = WhatsappMessage::whereIn('status', ['pending', 'failed'])
            // Optionally, limit to avoid spamming the API all at once
            // ->where('updated_at', '<=', now()->subMinutes(1)) // wait a bit before retrying failed
            ->orderBy('id', 'asc')
            ->take(50) // process in batches
            ->get();

        if ($messages->isEmpty()) {
            $this->info('No messages to process.');
            return;
        }

        $this->info("Found {$messages->count()} messages to process.");

        foreach ($messages as $message) {
            $this->info("Dispatching job for message ID: {$message->id}");
            // Dispatch to the default queue. 
            SendWhatsappMessage::dispatch($message->id);
        }

        $this->info('Outbox processing completed.');
    }
}
