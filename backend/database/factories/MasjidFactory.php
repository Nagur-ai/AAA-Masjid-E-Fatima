<?php

namespace Database\Factories;

use App\Models\Masjid;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Masjid>
 */
class MasjidFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Masjid '.fake()->unique()->lastName();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'address' => fake()->streetAddress(),
            'city' => 'London',
            'country' => 'GB',
            'latitude' => 51.5074,
            'longitude' => -0.1278,
            'elevation' => 0,
            'timezone' => 'Europe/London',
            'calculation_method' => 'mwl',
            'asr_method' => 'standard',
            'high_latitude_rule' => 'angle_based',
            'hijri_adjustment' => 0,
            'imsak_minutes_before_fajr' => 10,
            'currency' => 'GBP',
        ];
    }
}
