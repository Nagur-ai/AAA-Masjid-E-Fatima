<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A dua shown with the Ramadan calendar (sehri, iftar or other), managed by the masjid admin.
 */
#[Fillable(['type', 'title', 'body', 'sort_order', 'is_active'])]
class RamadanDua extends Model
{
    public const TYPES = ['sehri' => 'Sehri', 'iftar' => 'Iftar', 'other' => 'Other'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function masjid(): BelongsTo
    {
        return $this->belongsTo(Masjid::class);
    }

    /**
     * @return array{key: string, title_te: string, telugu: string}
     */
    public function toPublicArray(): array
    {
        return ['key' => $this->type, 'title_te' => $this->title, 'telugu' => $this->body];
    }
}
