<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SurveillanceReportMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public array $mailData,
        public string $attachmentContent,
        public string $attachmentName,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('Medical Surveillance Report')
            ->view('mail.surveillance-report')
            ->with(['mailData' => $this->mailData])
            ->attachData($this->attachmentContent, $this->attachmentName, [
                'mime' => 'application/pdf',
            ]);
    }
}
