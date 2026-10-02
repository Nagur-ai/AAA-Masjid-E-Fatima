<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PledgeDonationRequest extends FormRequest
{
    public function rules(): array
    {
        // Guests must leave a way to be contacted about their pledge.
        $guest = $this->user('sanctum') === null && $this->user() === null;

        return [
            'fund_id' => ['required', 'integer'],
            'amount' => ['required', 'decimal:0,2', 'min:1', 'max:10000000'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)->except([PaymentMethod::Cash])],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'donor_name' => [Rule::requiredIf($guest && ! $this->boolean('is_anonymous')), 'nullable', 'string', 'max:120'],
            'donor_email' => ['nullable', 'email', 'max:255'],
            'donor_phone' => [Rule::requiredIf($guest && ! $this->filled('donor_email')), 'nullable', 'string', 'max:32'],
            'is_anonymous' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
