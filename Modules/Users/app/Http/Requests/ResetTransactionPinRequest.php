<?php

namespace Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Users\Rules\TransactionPinRule;

class ResetTransactionPinRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'otp' => ['required', 'digits:4'],
            'transaction_pin' => ['required', 'string', 'confirmed', new TransactionPinRule],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
