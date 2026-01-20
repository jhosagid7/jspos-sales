<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProductionReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subjectLine;
    public $bodyContent;
    public $pdfContent;
    public $fileName;

    /**
     * Create a new message instance.
     */
    public function __construct($subject, $body, $pdfContent, $fileName)
    {
        $this->subjectLine = $subject;
        $this->bodyContent = $body;
        $this->pdfContent = $pdfContent;
        $this->fileName = $fileName;
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
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            \Illuminate\Mail\Mailables\Attachment::fromData(fn () => $this->pdfContent, $this->fileName)
                ->withMime('application/pdf'),
        ];
    }
}
