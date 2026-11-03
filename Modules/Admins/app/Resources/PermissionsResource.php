<?php

namespace Modules\Admins\Resources;

use App\Http\Traits\TranslateTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Admins\Models\Permissions;

/**
 * @mixin Permissions
 */
class PermissionsResource extends JsonResource
{
    use TranslateTrait;

    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->resource->id,
            'title' => $this->getTranslateByKeyInResourceFile('title', $this->resource->translate),
            'name' => $this->resource->name,
        ];

        if (isset($this->resource->permission_id) && isset($this->resource->model_id)) {
            $data['permission_id'] = $this->resource->permission_id;
            $data['model_id'] = $this->resource->model_id;
        }

        return $data;
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
