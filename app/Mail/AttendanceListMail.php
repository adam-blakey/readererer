<?php

namespace App\Mail;

use App\Models\Ensemble;
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
            subject: __('Attendance list — :name', ['name' => $this->termDate->name]),
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

    /** Members without an instrument family are still setting up their membership, so are left off, as on the poll. */
    private function members(): Collection
    {
        $ensembles = $this->termDate->is_concert ? $this->termDate->concert_ensembles : Ensemble::all();
        $users = fn ($query) => $query->wherePivotNotNull('instrument_family_id')
            ->with(['attendances' => fn ($query) => $query->where('term_date_id', $this->termDate->id)]);

        return $ensembles
            ->load(['users' => $users])
            ->flatMap->users
            ->unique('id')
            ->values();
    }
}
