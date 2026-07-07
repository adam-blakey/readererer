<?php

namespace App\Mail;

use App\Models\SetupGroup;
use App\Models\TermDate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SetupReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public TermDate $termDate, public SetupGroup $setupGroup) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Setup reminder — '.$this->termDate->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.setup-reminder',
            with: [
                'termDate' => $this->termDate,
                'setupGroup' => $this->setupGroup,
            ],
        );
    }
}
