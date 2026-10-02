<?php

namespace App\Services\PrayerTimes;

use App\Enums\Prayer;
use App\Services\Hijri\HijriDate;
use Carbon\CarbonImmutable;

final readonly class DailySchedule
{
    /**
     * @param  array<string, CarbonImmutable|null>  $times  Adhan/astronomical times keyed by PrayerTimeCalculator::TIMES
     * @param  array<string, CarbonImmutable|null>  $iqamah  Keyed by Prayer value
     * @param  list<array{label: string, khutbah: string, iqamah: string, language: string|null}>  $jumuah
     */
    public function __construct(
        public CarbonImmutable $date,
        public HijriDate $hijri,
        public array $times,
        public array $iqamah,
        public array $jumuah,
    ) {}

    /**
     * The next prayer (by adhan) after $now, or null once isha has passed.
     *
     * @return array{prayer: string, at: CarbonImmutable}|null
     */
    public function nextPrayer(CarbonImmutable $now): ?array
    {
        foreach (Prayer::cases() as $prayer) {
            $at = $this->times[$prayer->value];
            if ($at !== null && $at->greaterThan($now)) {
                return ['prayer' => $prayer->value, 'at' => $at];
            }
        }

        return null;
    }

    public function toArray(): array
    {
        $format = fn (?CarbonImmutable $t) => $t?->format('H:i');

        return [
            'date' => $this->date->toDateString(),
            'weekday' => $this->date->format('l'),
            'hijri' => $this->hijri->toArray(),
            'times' => array_map($format, $this->times),
            'iqamah' => array_map($format, $this->iqamah),
            'jumuah' => $this->jumuah,
        ];
    }
}
