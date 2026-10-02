<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['hijri_year', 'starts_on', 'total_days', 'is_confirmed', 'taraweeh_time', 'notes'])]
class RamadanCalendar extends Model
{
    protected function casts(): array
    {
        return [
            'hijri_year' => 'integer',
            'starts_on' => 'date',
            'total_days' => 'integer',
            'is_confirmed' => 'boolean',
        ];
    }

    public function masjid(): BelongsTo
    {
        return $this->belongsTo(Masjid::class);
    }
}
