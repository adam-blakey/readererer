<?php

namespace App\Mail;

use App\Models\Ensemble;
use App\Models\TermDate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AttendanceChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array{name: string, from: string, to: string}>  $changes
     */
    public function __construct(public TermDate $termDate, public Ensemble $ensemble, public array $changes) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Attendance changed — :name', ['name' => $this->termDate->name]),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.attendance-changed');
    }
}
