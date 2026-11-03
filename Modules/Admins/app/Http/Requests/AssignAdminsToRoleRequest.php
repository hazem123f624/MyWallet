<?php

namespace Modules\Admins\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignAdminsToRoleRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'admin_ids'   => ['required', 'array', 'min:1'],
            'admin_ids.*' => ['required', 'integer', 'distinct', Rule::exists('admins', 'id')->whereNull('deleted_at'),],
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
