<?php

namespace Modules\Admins\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Admins\Models\Models;

/**
 * @mixin Models
 */
class ModelsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'title' => $this->getTranslateByKey('title', $this->resource->translate),
            'permissions' => PermissionsResource::collection($this->resource->permissions),

        ];
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
