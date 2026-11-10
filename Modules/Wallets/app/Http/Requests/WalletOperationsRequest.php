<?php

namespace Modules\Wallets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Wallets\Enums\WalletOperationType;

class WalletOperationsRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = [
            'type' => ['nullable', Rule::in(array_column(WalletOperationType::cases(), 'value'))],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];

        if ($this->filled('date_from')) {
            $rules['date_to'][] = 'after_or_equal:date_from';
        }

        return $rules;
    }

    public function authorize(): bool
    {
        return true;
    }
}
