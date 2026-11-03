<?php

namespace Modules\Admins\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Admins\Models\Roles;

/**
 * @mixin Roles
 */
class RoleModulesPermissionsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return $this->resource->permissions
            ->groupBy('model_id')
            ->map(function ($permissions, $modelId) {
                $model = $permissions->first()->model;

                return [
                    'id' => $modelId,
                    'title' => $model ? $this->getTranslateByKey('title', $model->translate) : '',
                    'permissions' => PermissionsResource::collection($permissions),
                    ];
            })
            ->values()
            ->all();
    }

    protected function getTranslateByKey($key, $object)
    {
        $value = '';
        $object = $object->where('lang', app()->getLocale())->first();
        if ($object) {
            $value = $object->$key;
        }

        return $value;
    }
}
