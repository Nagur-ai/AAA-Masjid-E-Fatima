<?php

namespace App\Enums;

/**
 * Twilight angles used by the major authorities. Isha may be an angle or a fixed
 * number of minutes after maghrib; maghrib is sunset unless an angle is given.
 */
enum CalculationMethod: string
{
    case MWL = 'mwl';
    case ISNA = 'isna';
    case Egypt = 'egypt';
    case Makkah = 'makkah';
    case Karachi = 'karachi';
    case Tehran = 'tehran';
    case Jafari = 'jafari';
    case Gulf = 'gulf';
    case Kuwait = 'kuwait';
    case Qatar = 'qatar';
    case Singapore = 'singapore';
    case Turkey = 'turkey';
    case France = 'france';

    public function label(): string
    {
        return match ($this) {
            self::MWL => 'Muslim World League',
            self::ISNA => 'Islamic Society of North America',
            self::Egypt => 'Egyptian General Authority of Survey',
            self::Makkah => 'Umm al-Qura University, Makkah',
            self::Karachi => 'University of Islamic Sciences, Karachi',
            self::Tehran => 'Institute of Geophysics, University of Tehran',
            self::Jafari => 'Shia Ithna-Ashari, Leva Institute, Qum',
            self::Gulf => 'Gulf Region',
            self::Kuwait => 'Kuwait',
            self::Qatar => 'Qatar',
            self::Singapore => 'Majlis Ugama Islam Singapura',
            self::Turkey => 'Diyanet, Turkey',
            self::France => 'Union des Organisations Islamiques de France',
        };
    }

    /**
     * @return array{fajr: float, isha: float, isha_minutes: int|null, maghrib: float|null, midnight_from_fajr: bool}
     */
    public function parameters(): array
    {
        [$fajr, $isha, $ishaMinutes, $maghrib] = match ($this) {
            self::MWL => [18.0, 17.0, null, null],
            self::ISNA => [15.0, 15.0, null, null],
            self::Egypt => [19.5, 17.5, null, null],
            self::Makkah => [18.5, 0.0, 90, null],
            self::Karachi => [18.0, 18.0, null, null],
            self::Tehran => [17.7, 14.0, null, 4.5],
            self::Jafari => [16.0, 14.0, null, 4.0],
            self::Gulf => [19.5, 0.0, 90, null],
            self::Kuwait => [18.0, 17.5, null, null],
            self::Qatar => [18.0, 0.0, 90, null],
            self::Singapore => [20.0, 18.0, null, null],
            self::Turkey => [18.0, 17.0, null, null],
            self::France => [12.0, 12.0, null, null],
        };

        return [
            'fajr' => $fajr,
            'isha' => $isha,
            'isha_minutes' => $ishaMinutes,
            'maghrib' => $maghrib,
            'midnight_from_fajr' => in_array($this, [self::Tehran, self::Jafari], true),
        ];
    }
}
