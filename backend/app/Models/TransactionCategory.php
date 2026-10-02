<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'type', 'is_system'])]
class TransactionCategory extends Model
{
    /** Income category every donation is posted under. */
    public const DONATIONS = 'Donations';

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'is_system' => 'boolean',
        ];
    }

    public function masjid(): BelongsTo
    {
        return $this->belongsTo(Masjid::class);
    }
}
