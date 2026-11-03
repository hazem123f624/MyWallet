<?php

namespace Modules\Admins\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateModelRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title.ar' => 'required|string',
            'title.en' => 'required|string',
            'is_active' => 'nullable',
            'model_permissions' => 'required|array',
            'model_permissions.*' => 'required|distinct|unique:permissions,id',
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
