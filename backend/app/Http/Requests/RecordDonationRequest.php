<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A donation already received by the masjid (cash box, counter, verified transfer).
 */
class RecordDonationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'fund_id' => ['required', 'integer'],
            'amount' => ['required', 'decimal:0,2', 'min:0.01', 'max:100000000'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'donor_name' => ['nullable', 'string', 'max:120'],
            'donor_email' => ['nullable', 'email', 'max:255'],
            'donor_phone' => ['nullable', 'string', 'max:32'],
            'is_anonymous' => ['sometimes', 'boolean'],
            'donated_at' => ['nullable', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
