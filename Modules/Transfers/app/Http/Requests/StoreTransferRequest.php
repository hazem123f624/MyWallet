<?php

namespace Modules\Transfers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransferRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sub_wallet_id' => ['required', 'integer', 'exists:sub_wallets,id'],
            'beneficiary_id' => ['required', 'integer', 'exists:beneficiaries,id'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:999999999999.99'],
            'transaction_pin' => ['required', 'digits:4'],
            'client_reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
