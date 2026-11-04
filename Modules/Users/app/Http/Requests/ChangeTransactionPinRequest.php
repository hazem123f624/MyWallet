<?php

namespace Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Users\Rules\TransactionPinRule;

class ChangeTransactionPinRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'current_transaction_pin' => ['required', 'string', 'digits:4'],
            'transaction_pin' => [
                'required',
                'string',
                'confirmed',
                'different:current_transaction_pin',
                new TransactionPinRule,
            ],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
