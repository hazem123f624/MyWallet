<?php

namespace Modules\Wallets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RedistributeSubWalletRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'destination_sub_wallet_id' => ['required', 'integer', 'exists:sub_wallets,id'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:999999999999.99'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
