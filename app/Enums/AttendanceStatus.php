<?php

namespace App\Enums;

enum AttendanceStatus: int
{
    case Unknown = 0;
    case Attending = 1;
    case NotAttending = 2;
    case Depping = 3;

    /** An unknown reply counts as attending when the site assumes members attend, and a dep counts as attending while depping is off. */
    public function effective(): self
    {
        return match (true) {
            $this === self::Unknown && config('app.readererer_assume_attending') => self::Attending,
            $this === self::Depping && ! config('app.readererer_depping') => self::Attending,
            default => $this,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Unknown => __('Unknown'),
            self::Attending => __('Attending'),
            self::NotAttending => __('Not attending'),
            self::Depping => __('Sending a dep'),
        };
    }
}
