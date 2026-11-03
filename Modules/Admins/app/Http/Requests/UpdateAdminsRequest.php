<?php

namespace Modules\Admins\Http\Requests;

use App\Constants\RegexPatterns;
use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // Route param is {admin} (route-model bound); ignore the current admin
        // so the record can be updated without changing its own email/phone.
        $admin = $this->route('admin');

        return [
            'name' => ['sometimes', 'string', 'min:2', 'max:100', 'regex:'.RegexPatterns::NAME_WITH_REQUIRED_LETTER],
            'email' => ['sometimes', 'email:strict,dns', 'regex:'.RegexPatterns::EMAIL_NOT_NUMERIC_PREFIX, Rule::unique('admins', 'email')->ignore($admin)],
            'phone'   => ['sometimes', 'regex:'.RegexPatterns::PHONE_DIGITS_WITH_PLUS, 'min:9', 'max:20', 'unique:admins,phone'],
            'password' => ['nullable', new StrongPassword],
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'nullable|boolean',
            'roles' => 'sometimes|array|min:1',
            'roles.*.role_id' => 'sometimes|integer|exists:roles,id',
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
