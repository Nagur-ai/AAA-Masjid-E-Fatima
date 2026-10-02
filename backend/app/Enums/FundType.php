<?php

namespace App\Enums;

enum FundType: string
{
    case General = 'general';
    case Zakat = 'zakat';
    case Sadaqah = 'sadaqah';
    case Fitrah = 'fitrah';
    case Fidya = 'fidya';
    case Construction = 'construction';
    case Iftar = 'iftar';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Fitrah => 'Zakat al-Fitr',
            default => ucfirst($this->value),
        };
    }
}
