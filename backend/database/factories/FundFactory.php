<?php

namespace Database\Factories;

use App\Models\Fund;
use App\Models\Masjid;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fund>
 */
class FundFactory extends Factory
{
    public function definition(): array
    {
        return [
            'masjid_id' => Masjid::factory(),
            'name' => fake()->unique()->words(2, true).' fund',
            'type' => 'general',
            'is_restricted' => false,
            'is_active' => true,
        ];
    }

    public function restricted(): static
    {
        return $this->state(['type' => 'zakat', 'is_restricted' => true]);
    }
}
