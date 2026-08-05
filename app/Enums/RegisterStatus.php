<?php

namespace App\Enums;

enum RegisterStatus: int
{
    case Unmarked = 0;
    case Present = 1;
    case Absent = 2;
    case Late = 3;

    /**
     * The key this status is counted under in register_status_totals().
     */
    public function key(): string
    {
        return match ($this) {
            self::Unmarked => 'unmarked',
            self::Present => 'present',
            self::Absent => 'absent',
            self::Late => 'late',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Unmarked => 'Not marked',
            self::Present => 'Present',
            self::Absent => 'Absent',
            self::Late => 'Late',
        };
    }

    /**
     * The Tabler colour used for this status wherever it is rendered as a badge
     * or a button.
     */
    public function color(): string
    {
        return match ($this) {
            self::Unmarked => 'secondary',
            self::Present => 'green',
            self::Absent => 'red',
            self::Late => 'yellow',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Unmarked => 'question-mark',
            self::Present => 'check',
            self::Absent => 'x',
            self::Late => 'clock',
        };
    }

    /**
     * The statuses that can be chosen when taking a register, in the order they
     * are offered. `Unmarked` is a starting state rather than a choice.
     */
    public static function choices(): array
    {
        return [self::Present, self::Late, self::Absent];
    }
}
