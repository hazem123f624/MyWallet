<?php

namespace Modules\Admins\Http\Requests;

use App\Rules\ArabicLetters;
use App\Rules\EnglishLetters;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Admins\Models\Models;
use Modules\Admins\Models\Permissions;
use Modules\Admins\Rules\CheckPermissionInModule;

class UpdateRolesRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $role = $this->route('role');

        $permissions = [];
        foreach ($this->input('permissions', []) as $permission) {
            $modelData = Models::find($permission['model_id'] ?? null);
            $permissionData = Permissions::find($permission['permission_id'] ?? null);
            $permission['name'] = $modelData?->name.'_'.$permissionData?->name;
            $permissions[] = $permission;
        }

        $this->merge([
            'id' => $role?->id,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes',
            'name.ar' => ['sometimes', new ArabicLetters],
            'name.en' => ['sometimes', new EnglishLetters],

            'description' => 'nullable|array',
            'description.ar' => 'nullable|string',
            'description.en' => 'nullable|string',

            'permissions' => 'sometimes|array',
            'permissions.*.permission_id' => 'sometimes|integer|exists:permissions,id',
            'permissions.*.model_id' => ['sometimes', 'integer', new CheckPermissionInModule],
            'permissions.*.name' => 'nullable|string',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator): void {

            $permissions = $this->input('permissions', []);
            $seen = [];

            // check duplicate permission + model
            foreach ($permissions as $index => $permission) {
                if (! isset($permission['model_id']) || ! isset($permission['permission_id'])) {
                    continue;
                }
                $key = $permission['model_id'].'-'.$permission['permission_id'];
                if (isset($seen[$key])) {
                    $validator->errors()->add(
                        "permissions.$index.permission_id",
                        'This permission is already assigned to the same module.'
                    );
                }
                $seen[$key] = true;
            }
            // stop if validation failed
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
        });
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
