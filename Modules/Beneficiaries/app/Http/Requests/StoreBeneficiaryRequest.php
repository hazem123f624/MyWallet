<?php

namespace Modules\Beneficiaries\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBeneficiaryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->normalizeInputs();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'bank_name' => ['required', 'string', 'min:2', 'max:100'],
            'bank_code' => ['nullable', 'string', 'max:20'],
            'account_number' => ['required', 'string', 'regex:/^\d{8,34}$/'],
            'iban' => ['nullable', 'string', 'regex:/^[A-Z]{2}\d{2}[A-Z0-9]{10,30}$/'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    private function normalizeInputs(): void
    {
        if ($this->filled('account_number')) {
            $this->merge(['account_number' => preg_replace('/[\s-]+/', '', (string) $this->input('account_number'))]);
        }

        if ($this->filled('iban')) {
            $this->merge(['iban' => strtoupper(preg_replace('/\s+/', '', (string) $this->input('iban')))]);
        }
    }
}
