<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role->value,
            'masjid' => $this->whenLoaded('masjid', fn () => $this->masjid ? ['slug' => $this->masjid->slug, 'name' => $this->masjid->name] : null),
        ];
    }
}
