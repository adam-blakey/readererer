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

    private function members(): Collection
    {
        $ensembles = $this->termDate->is_concert
            ? $this->termDate->concert_ensembles->load('users')
            : Ensemble::with('users')->get();

        return $ensembles
            ->flatMap->users
            ->unique('id')
            ->values();
    }
}
