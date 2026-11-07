<?php

namespace Modules\BankAccounts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBankAccountRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('account_number')) {
            $this->merge([
                'account_number' => preg_replace('/[\s-]+/', '', (string) $this->input('account_number')),
            ]);
        }

        if ($this->filled('iban')) {
            $this->merge([
                'iban' => strtoupper(preg_replace('/\s+/', '', (string) $this->input('iban'))),
            ]);
        }

        if ($this->filled('account_holder_name')) {
            $this->merge([
                'account_holder_name' => trim((string) $this->input('account_holder_name')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'bank_id' => [
                'required',
                'integer',
                Rule::exists('banks', 'id')->where(
                    fn ($query) => $query->where('is_active', true)->where('supports_linking', true)
                ),
            ],
            'account_holder_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'regex:/^\d{8,34}$/'],
            'iban' => ['nullable', 'string', 'regex:/^[A-Z]{2}\d{2}[A-Z0-9]{10,30}$/'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
