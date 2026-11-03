<?php

namespace App\Classes\Authentication\Types\Users;

use App\Rules\StrongPassword;
use App\Constants\RegexPatterns;
use App\Traits\HasPhoneValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Modules\Users\Models\User;

class ResetPasswordRequest extends FormRequest
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

            'password' => [
                'required',
                'confirmed',
                new StrongPassword,
                function ($attribute, $value, $fail) {
                    $phone = $this->input('phone');
                    $user = User::where('phone', $phone)->first();
                    if ($user && Hash::check($value, $user->password)) {
                        $fail('The new password must be different from the old password.');
                    }
                },
            ],

            'change_password_token' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $phone = $this->input('phone');
                    $user = \Modules\Users\Models\User::where('phone', $phone)->first();
                    if (!$user || $user->change_password_token !== $value) {
                        $fail('Invalid or expired token.');
                    }
                },
            ],
        ];
    }
    public function authorize(): bool
    {
        return true;
    }
}
