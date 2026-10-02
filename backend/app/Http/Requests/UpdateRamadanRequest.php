<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRamadanRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'total_days' => ['required', 'integer', 'in:29,30'],
            'is_confirmed' => ['sometimes', 'boolean'],
            'taraweeh_time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
