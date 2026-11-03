<?php

namespace Modules\Admins\Http\Requests;

use App\Rules\ArabicLetters;
use App\Rules\EnglishLetters;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Admins\Models\Models;
use Modules\Admins\Models\Permissions;
use Modules\Admins\Rules\CheckPermissionInModule;

/**
 * @bodyParam name.ar string required The Arabic role name. Example: دور جديد
 * @bodyParam name.en string required The English role name. Example: New Role
 * @bodyParam description array nullable The role description translations.
 * @bodyParam description.ar string nullable The Arabic description. Example: هذا دور جديد
 * @bodyParam description.en string nullable The English description. Example: This is a new role
 */
class CreateRolesRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // 'required|unique:roles_translate,name,NULL,id,deleted_at,NULL',
        // 'required|unique:roles_translate,name,NULL,id,deleted_at,NULL',
        return [
            'name' => 'required',
            'name.ar' => ['required', new ArabicLetters],
            'name.en' => ['required', new EnglishLetters],

            'description' => 'nullable|array',
            'description.ar' => 'nullable|string',
            'description.en' => 'nullable|string',

            'permissions' => 'required|array|min:1',
            'permissions.*.permission_id' => 'required|integer|exists:permissions,id',
            'permissions.*.model_id' => ['required', 'integer', new CheckPermissionInModule],
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

    protected function prepareForValidation()
    {
        $permissions = [];
        foreach ($this->permissions ?? [] as $permission) {
            $modelData = Models::find(
                isset($permission['model_id']) ? (int) $permission['model_id'] : null
            );
            $permissionData = Permissions::find(
                isset($permission['permission_id']) ? (int) $permission['permission_id'] : null
            );
            $permission['name'] = $modelData?->name.'_'.$permissionData?->name;
            $permissions[] = $permission;
        }
        $this->merge([
            'permissions' => $permissions,
        ]);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
