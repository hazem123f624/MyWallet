<?php

namespace Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyTransactionPinRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'transaction_pin' => ['required', 'string', 'digits:4'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
