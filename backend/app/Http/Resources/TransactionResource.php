<?php

namespace App\Http\Resources;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Transaction */
class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference_no' => $this->reference_no,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'amount' => $this->amount,
            'payment_method' => $this->payment_method->value,
            'party_name' => $this->party_name,
            'description' => $this->description,
            'transacted_on' => $this->transacted_on->toDateString(),
            'category' => $this->whenLoaded('category', fn () => ['id' => $this->category->id, 'name' => $this->category->name]),
            'fund' => $this->whenLoaded('fund', fn () => $this->fund ? ['id' => $this->fund->id, 'name' => $this->fund->name] : null),
            'has_attachment' => $this->attachment_path !== null,
            'source' => $this->source_type ? ['type' => $this->source_type, 'id' => $this->source_id] : null,
            'recorded_by' => $this->whenLoaded('recorder', fn () => $this->recorder->name),
            'voided_at' => $this->voided_at?->toIso8601String(),
            'void_reason' => $this->void_reason,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
