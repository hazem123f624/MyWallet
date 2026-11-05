<?php

namespace Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Users\Rules\TransactionPinRule;

class StoreTransactionPinRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'transaction_pin' => ['required', 'string', 'confirmed', new TransactionPinRule],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
