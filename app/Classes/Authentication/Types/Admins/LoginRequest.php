<?php

namespace App\Classes\Authentication\Types\Admins;

use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'step' => 'required|integer|in:1,2',
            'email' => 'required|email|exists:admins,email',
            'password' => ['required_if:step,1', new StrongPassword],
            'otp' => 'required_if:step,2|digits:4',
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
