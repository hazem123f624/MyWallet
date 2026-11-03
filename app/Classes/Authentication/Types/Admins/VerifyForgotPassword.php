<?php

namespace App\Classes\Authentication\Types\Admins;

use Illuminate\Foundation\Http\FormRequest;

class VerifyForgotPassword extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email|exists:admins,email',
            'otp' => 'required|digits:4',
            'change_password_token' => 'required|exists:admins,change_password_token',
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
