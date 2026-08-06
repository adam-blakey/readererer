<?php

namespace App\Mail;

use App\Models\TermDate;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VanDriverReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public TermDate $termDate, public User $vanDriver) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Van driver reminder — '.$this->termDate->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.van-driver-reminder',
            with: [
                'termDate' => $this->termDate,
                'vanDriver' => $this->vanDriver,
            ],
        );
    }
}
