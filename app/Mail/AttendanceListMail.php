<?php

namespace App\Mail;

use App\Models\Ensemble;
use App\Models\InstrumentFamily;
use App\Models\TermDate;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class AttendanceListMail extends Mailable
{
    use Queueable, SerializesModels;

    private bool $rendering = false;

    public function __construct(public TermDate $termDate, public ?User $recipient = null) {}

    /** Rendering for a preview, snapshot or log needs only the body, so it skips building the seating plan PDFs. */
    public function render()
    {
        $this->rendering = true;

        try {
            return parent::render();
        } finally {
            $this->rendering = false;
        }
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Attendance list — :name', ['name' => $this->termDate->name]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.attendance-list-sections',
            with: [
                'termDate' => $this->termDate,
                'members' => $members = $this->members(),
                'families' => InstrumentFamily::whereIn('id', $members->pluck('pivot.instrument_family_id'))->get()->keyBy('id'),
                'sentAt' => now(),
            ],
        );
    }

    /** A seating plan for each of the date's ensembles that has one. */
    public function attachments(): array
    {
        if ($this->rendering) {
            return [];
        }

        return $this->termDate->notifiedEnsembles()
            ->filter->hasSeatingPlan()
            ->map(fn (Ensemble $ensemble) => Attachment::fromData(
                fn () => $ensemble->seatingPlanPdf($this->termDate)->output(),
                __('Seating plan - :ensemble.pdf', ['ensemble' => $ensemble->name]),
            )->withMime('application/pdf'))
            ->values()
            ->all();
    }

    /** Members without an instrument family are still setting up their membership, so are left off, as on the poll. */
    private function members(): Collection
    {
        $users = fn ($query) => $query->wherePivotNotNull('instrument_family_id')
            ->with(['attendances' => fn ($query) => $query->where('term_date_id', $this->termDate->id)]);

        return $this->termDate->notifiedEnsembles()
            ->load(['users' => $users])
            ->flatMap->users
            ->unique('id')
            ->values();
    }
}
