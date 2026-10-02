<?php

namespace App\Http\Requests;

use App\Enums\AsrMethod;
use App\Enums\CalculationMethod;
use App\Enums\HighLatitudeRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMasjidRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:80'],
            'country' => ['nullable', 'string', 'size:2'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'elevation' => ['nullable', 'integer', 'between:0,9000'],
            'timezone' => ['required', 'timezone:all'],
            'calculation_method' => ['required', Rule::enum(CalculationMethod::class)],
            'asr_method' => ['required', Rule::enum(AsrMethod::class)],
            'high_latitude_rule' => ['required', Rule::enum(HighLatitudeRule::class)],
            'hijri_adjustment' => ['required', 'integer', 'between:-2,2'],
            'imsak_minutes_before_fajr' => ['required', 'integer', 'between:0,30'],
            'currency' => ['required', 'string', 'size:3'],
            'bank_details' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
