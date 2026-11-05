<?php

namespace App\Classes\Authentication\Types\Users;

use App\Constants\RegexPatterns;
use App\Traits\HasPhoneValidation;
use Illuminate\Foundation\Http\FormRequest;

class VerifyForgotPassword extends FormRequest
{
    use HasPhoneValidation;

    protected function prepareForValidation()
    {
        if ($this->filled('country_code') && $this->filled('phone')) {
            $fullPhone = $this->getFullPhoneNumber($this->phone, $this->country_code);
            
            $this->merge([
                'full_phone' => $fullPhone,
            ]);
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

            'otp' => 'required|digits:4',
            'full_phone' => [
                'nullable',
                'string',
            ],
            'change_password_token' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $phone = $this->input('full_phone') ?? $this->input('phone');
                    $user = \Modules\Users\Models\User::where('phone', $phone)->first();
                    
                    if (!$user || $user->change_password_token !== $value) {
                        $fail('Invalid or expired token.');
                    }
                },
            ],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
