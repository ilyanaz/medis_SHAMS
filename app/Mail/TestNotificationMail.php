<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestNotificationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $recipientEmail,
        public string $providerName,
        public string $appName,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Medis SHAMS Email Test',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.test-notification',
        );
    }
}
