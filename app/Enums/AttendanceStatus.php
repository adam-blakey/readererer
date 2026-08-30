<?php

namespace App\Enums;

enum AttendanceStatus: int
{
    case Unknown = 0;
    case Attending = 1;
    case NotAttending = 2;

    public function label(): string
    {
        return match ($this) {
            self::Unknown => __('Unknown'),
            self::Attending => __('Attending'),
            self::NotAttending => __('Not attending'),
        };
    }
}