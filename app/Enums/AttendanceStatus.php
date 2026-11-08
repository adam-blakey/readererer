<?php

namespace App\Enums;

enum AttendanceStatus: int
{
    case Unknown = 0;
    case Attending = 1;
    case NotAttending = 2;

    /** An unknown reply counts as attending when the site assumes members attend. */
    public function effective(): self
    {
        return $this === self::Unknown && config('app.readererer_assume_attending') ? self::Attending : $this;
    }

    public function label(): string
    {
        return match ($this) {
            self::Unknown => __('Unknown'),
            self::Attending => __('Attending'),
            self::NotAttending => __('Not attending'),
        };
    }
}
