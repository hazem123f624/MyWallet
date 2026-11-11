<?php

namespace Modules\Beneficiaries\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBeneficiaryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('account_number')) {
            $this->merge(['account_number' => preg_replace('/[\s-]+/', '', (string) $this->input('account_number'))]);
        }

        if ($this->filled('iban')) {
            $this->merge(['iban' => strtoupper(preg_replace('/\s+/', '', (string) $this->input('iban')))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:100'],
            'bank_name' => ['sometimes', 'required', 'string', 'min:2', 'max:100'],
            'bank_code' => ['sometimes', 'nullable', 'string', 'max:20'],
            'account_number' => ['sometimes', 'required', 'string', 'regex:/^\d{8,34}$/'],
            'iban' => ['sometimes', 'nullable', 'string', 'regex:/^[A-Z]{2}\d{2}[A-Z0-9]{10,30}$/'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
