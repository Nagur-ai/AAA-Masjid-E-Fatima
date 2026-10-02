<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(TransactionType::class)],
            'category_id' => ['required', 'integer'],
            'fund_id' => ['nullable', 'integer'],
            'amount' => ['required', 'decimal:0,2', 'min:0.01', 'max:100000000'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'party_name' => ['nullable', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:255'],
            'transacted_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    /**
     * Validated data with the uploaded receipt/invoice stored privately.
     */
    public function ledgerData(): array
    {
        $data = $this->safe()->except('attachment');
        if ($this->hasFile('attachment')) {
            $data['attachment_path'] = $this->file('attachment')->store('ledger-attachments');
        }

        return $data;
    }
}
