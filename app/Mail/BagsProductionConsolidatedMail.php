<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BagsProductionConsolidatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subjectLine;
    public $bodyContent;
    public $pdfs; // Array of ['content' => $pdfContent, 'name' => $fileName]

    /**
     * Create a new message instance.
     */
    public function __construct($subject, $body, array $pdfs)
    {
        $this->subjectLine = $subject;
        $this->bodyContent = $body;
        $this->pdfs = array_map(function ($pdf) {
            $rawContent = $pdf['content'] ?? '';
            return [
                'content' => $rawContent ? base64_encode($rawContent) : '',
                'name' => $pdf['name'] ?? 'document.pdf',
            ];
        }, $pdfs);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            htmlString: $this->bodyContent,
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        $attachments = [];

        foreach ($this->pdfs as $pdf) {
            $binaryData = base64_decode($pdf['content'] ?? '');
            $attachments[] = \Illuminate\Mail\Mailables\Attachment::fromData(
                fn () => $binaryData,
                $pdf['name']
            )->withMime('application/pdf');
        }

        return $attachments;
    }
}
