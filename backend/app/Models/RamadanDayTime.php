<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Date, Telugu day name and sehri/iftar time set by the admin for one day of Ramadan;
 * null means "use the calculated value".
 */
#[Fillable(['hijri_year', 'day', 'date', 'day_label', 'sehri', 'iftar'])]
class RamadanDayTime extends Model
{
    /** Telugu day names the admin can pick from, Sunday first. */
    public const DAY_NAMES = ['ఆదివారం', 'సోమవారం', 'మంగళవారం', 'బుధవారం', 'గురువారం', 'శుక్రవారం', 'శనివారం'];

    /** Short forms, as on the masjid's printed timetable. */
    public const DAY_NAMES_SHORT = ['ఆది', 'సోమ', 'మంగళ', 'బుధ', 'గురు', 'శుక్ర', 'శని'];

    protected function casts(): array
    {
        return [
            'hijri_year' => 'integer',
            'day' => 'integer',
            'date' => 'date:Y-m-d',
        ];
    }

    public function masjid(): BelongsTo
    {
        return $this->belongsTo(Masjid::class);
    }
}
