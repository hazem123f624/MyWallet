<?php

namespace Modules\Admins\Http\Requests;

use App\Constants\RegexPatterns;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * Only editable, non-sensitive fields are allowed here.
     * password / permissions / roles are intentionally excluded — they are
     * handled by dedicated endpoints (see ChangePasswordRequest, role management).
     */
    public function rules(): array
    {
        $adminId = auth('sanctum')->id();

        return [
            'name'          => ['sometimes', 'string', 'max:255'],
            //'email'         => ['sometimes', 'email', 'max:255', Rule::unique('admins', 'email')->ignore($adminId)],
            'phone'         => ['sometimes', 'nullable', 'regex:'.RegexPatterns::PHONE_DIGITS_WITH_PLUS, 'max:20', Rule::unique('admins', 'phone')->ignore($adminId)],
            'avatar'        => ['sometimes', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'delete_avatar' => ['sometimes', 'boolean'],
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
