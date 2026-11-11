<?php

namespace Modules\Wallets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubWalletRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:1', 'max:100'],
            'color' => ['required', 'string', Rule::in(config('wallets.sub_wallet_colors', []))],
            'icon' => ['required', 'string', Rule::in(config('wallets.sub_wallet_icons', []))],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
