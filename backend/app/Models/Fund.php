<?php

namespace App\Models;

use App\Enums\FundType;
use Database\Factories\FundFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'type', 'description', 'target_amount', 'is_restricted', 'is_active'])]
class Fund extends Model
{
    /** @use HasFactory<FundFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => FundType::class,
            'target_amount' => 'decimal:2',
            'is_restricted' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function masjid(): BelongsTo
    {
        return $this->belongsTo(Masjid::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
