<?php

namespace App\Models;

use App\Enums\Prayer;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Adhan/iqamah time set by the admin for one prayer on one date; null means "use the calculation".
 */
#[Fillable(['date', 'prayer', 'adhan', 'iqamah'])]
class PrayerTimeOverride extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'prayer' => Prayer::class,
        ];
    }

    public function masjid(): BelongsTo
    {
        return $this->belongsTo(Masjid::class);
    }
}
