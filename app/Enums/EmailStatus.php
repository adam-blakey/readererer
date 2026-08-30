<?php

namespace App\Enums;

enum EmailStatus: int
{
    case Pending = 0;
    case Sent = 1;
    case Failed = 2;

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::Sent => __('Sent'),
            self::Failed => __('Failed'),
        };
    }
}
