<?php

namespace App\Enums;

enum Prayer: string
{
    case Fajr = 'fajr';
    case Dhuhr = 'dhuhr';
    case Asr = 'asr';
    case Maghrib = 'maghrib';
    case Isha = 'isha';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
