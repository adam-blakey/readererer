<?php

namespace App\Enums;

enum ColorName: string
{
    case Blue = 'blue';
    case Azure = 'azure';
    case Indigo = 'indigo';
    case Purple = 'purple';
    case Pink = 'pink';
    case Red = 'red';
    case Orange = 'orange';
    case Yellow = 'yellow';
    case Lime = 'lime';
    case Green = 'green';
    case Teal = 'teal';
    case Cyan = 'cyan';

    public function hex(): string
    {
        return match ($this) {
            self::Blue => '#066fd1',
            self::Azure => '#4299e1',
            self::Indigo => '#4263eb',
            self::Purple => '#ae3ec9',
            self::Pink => '#d6336c',
            self::Red => '#d63939',
            self::Orange => '#f76707',
            self::Yellow => '#f59f00',
            self::Lime => '#74b816',
            self::Green => '#2fb344',
            self::Teal => '#0ca678',
            self::Cyan => '#17a2b8',
        };
    }

    public function cssClass(): string
    {
        return $this->value;
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
