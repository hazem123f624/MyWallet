<?php

namespace Modules\Admins\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Admins\Models\ModelPermissions;

class CheckPermissionInModule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $permission = explode('.', $attribute);
        $permission = $permission[0].'.'.$permission[1].'.permission_id';
        $permission_id = request()->$permission;
        $exists = ModelPermissions::where('model_id', $value)->where('permission_id', $permission_id)->exists();
//        if (! $exists) {
//            $fail('Invalid permission for the selected module.');
//        }
    }
}
