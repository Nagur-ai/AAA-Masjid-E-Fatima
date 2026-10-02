<?php

namespace App\Http\Requests;

use App\Enums\IqamahMode;
use App\Enums\Prayer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIqamahRuleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'prayer' => ['required', Rule::enum(Prayer::class)],
            'mode' => ['required', Rule::enum(IqamahMode::class)],
            'fixed_time' => ['required_if:mode,fixed', 'nullable', 'date_format:H:i'],
            'offset_minutes' => ['required_if:mode,offset', 'nullable', 'integer', 'between:0,120'],
            'round_to_minutes' => ['nullable', 'integer', 'in:0,5,10,15'],
            'effective_from' => ['required', 'date_format:Y-m-d'],
            'effective_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:effective_from'],
        ];
    }
}
