<?php

namespace Modules\BankAccounts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyBankAccountRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'otp' => ['required', 'digits:4'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
