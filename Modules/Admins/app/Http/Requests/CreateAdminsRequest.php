<?php

namespace Modules\Admins\Http\Requests;

use App\Constants\RegexPatterns;
use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

class CreateAdminsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name'                  => ['required', 'string', 'min:2', 'max:100', 'regex:'.RegexPatterns::NAME_WITH_REQUIRED_LETTER],
            'email'                 => ['required', 'email', 'regex:'.RegexPatterns::EMAIL_NOT_NUMERIC_PREFIX, 'unique:admins,email'],
            'phone'                 => ['required', 'regex:'.RegexPatterns::PHONE_DIGITS_WITH_PLUS, 'min:9', 'max:20', 'unique:admins,phone'],
            'password'              => ['required', 'confirmed', new StrongPassword],
            'password_confirmation' => ['required', 'same:password'],
            'avatar'                => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active'             => 'nullable|boolean',
            'roles'                 => 'required|array|min:1',
            'roles.*.role_id'       => 'required|integer|exists:roles,id',
            'job_title'             => ['required', 'string', 'min:2', 'max:100'],
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
