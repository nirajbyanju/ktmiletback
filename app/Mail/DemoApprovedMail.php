<?php

namespace App\Mail;

use App\Models\DemoRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DemoApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly DemoRequest $demoRequest,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Live Demo Class is Confirmed — KTM Test Prep',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.demo-approved',
            with: ['demoRequest' => $this->demoRequest],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
