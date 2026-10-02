<?php

namespace App\Enums;

enum AsrMethod: string
{
    /** Shafi'i, Maliki, Hanbali: shadow length = object length. */
    case Standard = 'standard';
    /** Hanafi: shadow length = twice object length. */
    case Hanafi = 'hanafi';

    public function shadowFactor(): int
    {
        return $this === self::Hanafi ? 2 : 1;
    }

    public function label(): string
    {
        return $this === self::Hanafi ? 'Hanafi' : "Standard (Shafi'i, Maliki, Hanbali)";
    }
}
