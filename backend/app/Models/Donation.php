<?php

namespace App\Models;

use App\Enums\DonationStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'fund_id', 'user_id', 'donor_name', 'donor_email', 'donor_phone', 'is_anonymous',
    'amount', 'currency', 'payment_method', 'payment_reference', 'notes',
])]
class Donation extends Model
{
    protected function casts(): array
    {
        return [
            'status' => DonationStatus::class,
            'payment_method' => PaymentMethod::class,
            'amount' => 'decimal:2',
            'is_anonymous' => 'boolean',
            'donated_at' => 'datetime',
        ];
    }

    public function masjid(): BelongsTo
    {
        return $this->belongsTo(Masjid::class);
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function displayName(): string
    {
        return $this->is_anonymous ? 'Anonymous' : ($this->donor_name ?: 'Anonymous');
    }
}
