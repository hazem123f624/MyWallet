<?php

namespace App\Http\Requests\User;

use App\Constants\RegexPatterns;
use App\Traits\HasPhoneValidation;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    use HasPhoneValidation;

    protected function prepareForValidation(): void
    {
        if ($this->filled('country_code') && $this->filled('phone')) {
            $this->merge([
                'full_phone' => $this->getFullPhoneNumber($this->input('phone'), $this->input('country_code')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'country_code' => [
                'required',
                'string',
                function ($attribute, $value, $fail): void {
                    if (! $this->isValidCountryCode($value)) {
                        $fail(__('users::messages.invalid_country_code'));
                    }
                },
            ],
            'phone' => [
                'required',
                'regex:'.RegexPatterns::PHONE_DIGITS,
                'max:20',
                function ($attribute, $value, $fail): void {
                    try {
                        $this->validatePhoneNumber($value, $this->input('country_code'));
                    } catch (\Exception $exception) {
                        $fail($exception->getMessage());
                    }
                },
            ],
            'full_phone' => ['nullable', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
