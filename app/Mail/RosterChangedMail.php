<?php

namespace App\Mail;

use App\Models\TermDate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RosterChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, string>  $changes  Human-readable descriptions of what changed.
     */
    public function __construct(public TermDate $termDate, public array $changes) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Setup group / van driver changed — '.$this->termDate->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.roster-changed',
            with: [
                'termDate' => $this->termDate,
                'changes' => $this->changes,
            ],
        );
    }
}
