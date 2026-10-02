<?php

namespace App\Http\Requests;

use App\Enums\FundType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFundRequest extends FormRequest
{
    public function rules(): array
    {
        $masjidId = $this->route('masjid')?->id ?? $this->user()->masjid_id;
        $fund = $this->route('fund');

        return [
            'name' => ['required', 'string', 'max:80',
                Rule::unique('funds')->where('masjid_id', $masjidId)->ignore($fund?->id)],
            'type' => ['required', Rule::enum(FundType::class)],
            'description' => ['nullable', 'string', 'max:500'],
            'target_amount' => ['nullable', 'decimal:0,2', 'min:0'],
            'is_restricted' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
