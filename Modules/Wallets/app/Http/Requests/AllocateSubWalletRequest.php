<?php

namespace Modules\Wallets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AllocateSubWalletRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:999999999999.99'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
