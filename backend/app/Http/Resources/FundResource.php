<?php

namespace App\Http\Resources;

use App\Models\Fund;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Fund */
class FundResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'description' => $this->description,
            'target_amount' => $this->target_amount,
            'is_restricted' => $this->is_restricted,
            'is_active' => $this->is_active,
        ];
    }
}
