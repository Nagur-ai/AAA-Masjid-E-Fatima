<?php

namespace App\Services\Hijri;

final readonly class HijriDate
{
    public const MONTHS = [
        1 => 'Muharram', 2 => 'Safar', 3 => "Rabi' al-Awwal", 4 => "Rabi' al-Thani",
        5 => 'Jumada al-Ula', 6 => 'Jumada al-Akhirah', 7 => 'Rajab', 8 => "Sha'ban",
        9 => 'Ramadan', 10 => 'Shawwal', 11 => "Dhu al-Qi'dah", 12 => 'Dhu al-Hijjah',
    ];

    public function __construct(
        public int $year,
        public int $month,
        public int $day,
    ) {}

    public function monthName(): string
    {
        return self::MONTHS[$this->month];
    }

    public function format(): string
    {
        return "{$this->day} {$this->monthName()} {$this->year} AH";
    }

    /**
     * @return array{year: int, month: int, day: int, month_name: string, formatted: string}
     */
    public function toArray(): array
    {
        return [
            'year' => $this->year,
            'month' => $this->month,
            'day' => $this->day,
            'month_name' => $this->monthName(),
            'formatted' => $this->format(),
        ];
    }
}
