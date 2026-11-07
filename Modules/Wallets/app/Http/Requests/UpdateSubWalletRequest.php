<?php

namespace Modules\Wallets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubWalletRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'min:1', 'max:100'],
            'color' => ['sometimes', 'required', 'string', Rule::in(config('wallets.sub_wallet_colors', []))],
            'icon' => ['sometimes', 'required', 'string', Rule::in(config('wallets.sub_wallet_icons', []))],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
