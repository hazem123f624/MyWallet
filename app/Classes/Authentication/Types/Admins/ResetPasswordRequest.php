<?php

namespace App\Classes\Authentication\Types\Admins;

use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Modules\Admins\Models\Admins;

class ResetPasswordRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $admin = Admins::where('email', $this->input('email'))->first();

        return [
            'email' => 'required|email|exists:admins,email',
            'password' => ['required', new StrongPassword,
                function ($attribute, $value, $fail) use ($admin) {
                    // Prevent reusing the same password
                    if ($admin && Hash::check($value, $admin->password)) {
                        $fail('The new password must be different from your current password.');
                    }
                },
            ],
            'new_password_confirmation' => ['required', 'same:password'],
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
