<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['label', 'khutbah_time', 'iqamah_time', 'language', 'sort_order'])]
class JumuahSession extends Model
{
    public function masjid(): BelongsTo
    {
        return $this->belongsTo(Masjid::class);
    }
}
