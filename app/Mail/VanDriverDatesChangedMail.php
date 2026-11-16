<?php

namespace App\Mail;

use App\Models\TermDate;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class VanDriverDatesChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, TermDate>  $termDates  The driver's next van driving dates.
     */
    public function __construct(public ?User $vanDriver, public Collection $termDates) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Your van driving dates have changed'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.van-driver-dates-changed',
            with: [
                'vanDriver' => $this->vanDriver,
                'termDates' => $this->termDates,
            ],
        );
    }
}
