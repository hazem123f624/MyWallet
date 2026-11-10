<?php

namespace Modules\Transfers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Transfers\Enums\TransferStatus;

class TransferHistoryRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = [
            'reference' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(array_column(TransferStatus::cases(), 'value'))],
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
