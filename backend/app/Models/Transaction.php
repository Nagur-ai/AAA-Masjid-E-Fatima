<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A ledger entry. Created only through LedgerService; never updated except to void.
 */
#[Fillable([
    'reference_no', 'type', 'category_id', 'fund_id', 'amount', 'payment_method',
    'party_name', 'description', 'transacted_on', 'status', 'attachment_path', 'recorded_by',
])]
class Transaction extends Model
{
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'status' => TransactionStatus::class,
            'payment_method' => PaymentMethod::class,
            'amount' => 'decimal:2',
            'transacted_on' => 'date',
            'voided_at' => 'datetime',
        ];
    }

    public function masjid(): BelongsTo
    {
        return $this->belongsTo(Masjid::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class);
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function scopePosted(Builder $query): void
    {
        $query->where('status', TransactionStatus::Posted);
    }

    public function isVoided(): bool
    {
        return $this->status === TransactionStatus::Voided;
    }
}
