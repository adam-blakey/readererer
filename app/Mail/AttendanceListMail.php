<?php

namespace App\Mail;

use App\Models\TermDate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AttendanceListMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public TermDate $termDate) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Attendance list — '.$this->termDate->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.attendance-list',
            with: [
                'termDate' => $this->termDate,
                'members' => $this->termDate->players(),
            ],
        );
    }
}
