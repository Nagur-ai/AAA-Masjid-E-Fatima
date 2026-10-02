<?php

namespace App\Enums;

/**
 * How fajr/isha are bounded when the sun never reaches the twilight angle
 * (summer nights above ~48° latitude).
 */
enum HighLatitudeRule: string
{
    case None = 'none';
    case MiddleOfNight = 'middle_of_night';
    case OneSeventh = 'one_seventh';
    case AngleBased = 'angle_based';

    public function label(): string
    {
        return match ($this) {
            self::None => 'None',
            self::MiddleOfNight => 'Middle of the night',
            self::OneSeventh => 'One-seventh of the night',
            self::AngleBased => 'Angle-based',
        };
    }
}
