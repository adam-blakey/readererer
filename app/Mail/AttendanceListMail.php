<?php

namespace App\Mail;

use App\Features\AttendanceListSections;
use App\Models\Ensemble;
use App\Models\TermDate;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Laravel\Pennant\Feature;

class AttendanceListMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public TermDate $termDate, public ?User $recipient = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Attendance list — :name', ['name' => $this->termDate->name]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: Feature::for($this->recipient)->active(AttendanceListSections::class) ? 'emails.attendance-list-sections' : 'emails.attendance-list',
            with: [
                'termDate' => $this->termDate,
                'members' => $this->members(),
                'sentAt' => now(),
            ],
        );
    }

    /** A seating plan for each of the date's ensembles that has one. */
    public function attachments(): array
    {
        return $this->ensembles()
            ->filter->hasSeatingPlan()
            ->map(fn (Ensemble $ensemble) => Attachment::fromData(
                fn () => $ensemble->seatingPlanPdf($this->termDate)->output(),
                __('Seating plan - :ensemble.pdf', ['ensemble' => $ensemble->name]),
            )->withMime('application/pdf'))
            ->values()
            ->all();
    }

    private function ensembles(): Collection
    {
        return $this->termDate->is_concert ? $this->termDate->concert_ensembles : Ensemble::all();
    }

    /** Members without an instrument family are still setting up their membership, so are left off, as on the poll. */
    private function members(): Collection
    {
        $users = fn ($query) => $query->wherePivotNotNull('instrument_family_id')
            ->with(['attendances' => fn ($query) => $query->where('term_date_id', $this->termDate->id)]);

        return $this->ensembles()
            ->load(['users' => $users])
            ->flatMap->users
            ->unique('id')
            ->values();
    }
}
