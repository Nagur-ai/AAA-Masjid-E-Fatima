<?php

namespace App\Http\Resources;

use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Donation */
class DonationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'receipt_no' => $this->receipt_no,
            'status' => $this->status->value,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'payment_method' => $this->payment_method->value,
            'payment_reference' => $this->payment_reference,
            'donor_name' => $this->displayName(),
            'donor_email' => $this->when(! $this->is_anonymous, $this->donor_email),
            'donor_phone' => $this->when(! $this->is_anonymous, $this->donor_phone),
            'is_anonymous' => $this->is_anonymous,
            'notes' => $this->notes,
            'donated_at' => $this->donated_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'fund' => new FundResource($this->whenLoaded('fund')),
            'masjid' => $this->whenLoaded('masjid', fn () => ['slug' => $this->masjid->slug, 'name' => $this->masjid->name]),
            'transaction_reference' => $this->whenLoaded('transaction', fn () => $this->transaction?->reference_no),
        ];
    }
}
