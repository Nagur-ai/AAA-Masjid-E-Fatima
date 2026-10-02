<?php

namespace App\Http\Resources;

use App\Models\Masjid;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Masjid */
class MasjidResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->country,
            'phone' => $this->phone,
            'email' => $this->email,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'timezone' => $this->timezone,
            'currency' => $this->currency,
            'calculation_method' => [
                'code' => $this->calculation_method->value,
                'name' => $this->calculation_method->label(),
            ],
            'asr_method' => $this->asr_method->value,
            'hijri_adjustment' => $this->hijri_adjustment,
            'bank_details' => $this->bank_details,
            'distance_km' => $this->whenHas('distance_km', fn () => round((float) $this->distance_km, 2)),
            'jumuah' => $this->whenLoaded('jumuahSessions', fn () => $this->jumuahSessions->map(fn ($s) => [
                'label' => $s->label,
                'khutbah' => substr($s->khutbah_time, 0, 5),
                'iqamah' => substr($s->iqamah_time, 0, 5),
                'language' => $s->language,
            ])),
        ];
    }
}
