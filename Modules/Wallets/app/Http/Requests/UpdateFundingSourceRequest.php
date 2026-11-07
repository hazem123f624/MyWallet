<?php

namespace Modules\Wallets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFundingSourceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'bank_account_id' => ['required', 'integer', 'exists:bank_accounts,id'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
