<?php

namespace App\Classes\Authentication\Types\Users;

use App\Rules\StrongPassword;
use App\Constants\RegexPatterns;
use App\Traits\HasPhoneValidation;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
   use HasPhoneValidation;

    protected function prepareForValidation()
    {
        if ($this->filled('country_code') && $this->filled('phone')) {
            $countryId = $this->getCountryIdFromCode($this->country_code);
            
            if ($countryId) {
                $this->merge([
                    'country_id' => $countryId,
                    'full_phone' => $this->getFullPhoneNumber($this->phone, $this->country_code),
                ]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
             'country_code' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (!$this->isValidCountryCode($value)) {
                        $fail('Invalid country code selected.');
                    }
                },
            ],
            'phone' => [
                'required',
                'regex:'.RegexPatterns::PHONE_DIGITS,
                'max:20',
                function ($attribute, $value, $fail) {
                    if (!$this->country_code) {
                        return; // country_code's own 'required' rule will catch this
                    }
                    try {
                        $this->validatePhoneNumber($value, $this->country_code);
                    } catch (\Exception $e) {
                        $fail($e->getMessage());
                    }
                },
            ],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate phone length based on selected country.
     */
    protected function validatePhoneLength($phone, $fail): void
    {
        $countryCode = $this->input('country_code');
        
        if (!$countryCode) {
            return;
        }

        $country = \Modules\Settings\Models\Settings::countries()
            ->where('data->phone_code', $countryCode)
            ->first();
        
        if (!$country) {
            $fail('Invalid country selected.');
            return;
        }

        $phoneLength = strlen($phone);
        $countryData = $country->data ?? [];
        $expectedLength = $countryData['phone_length'] ?? 9;

        if ($phoneLength !== $expectedLength) {
            $fail("Phone number must be {$expectedLength} digits for {$country->name_en}.");
        }
    }
}
