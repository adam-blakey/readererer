<?php

namespace App\Mail;

use App\Models\TermDate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

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
                'members' => $this->members(),
            ],
        );
    }

    /**
     * The members whose attendance is relevant to this date: the concert
     * ensemble's players for a concert, or every ensemble's players for a
     * shared rehearsal.
     */
    private function members(): Collection
    {
        if ($this->termDate->concert_ensemble_id !== null) {
            return ($this->termDate->concert_ensemble?->users ?? collect())->values();
        }

        return \App\Models\Ensemble::with('users')
            ->get()
            ->flatMap->users
            ->unique('id')
            ->values();
    }
}
