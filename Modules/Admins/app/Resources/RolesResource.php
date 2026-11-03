<?php

namespace Modules\Admins\Resources;

use App\Http\Traits\TranslateTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Admins\Models\Roles;

/**
 * @mixin Roles
 */
class RolesResource extends JsonResource
{
    use TranslateTrait;

    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        if (isset($this->resource->role)) {
            $resource = $this->resource->role;
        } else {
            $resource = $this->resource;
        }

        return [
            'id'                 => $resource->id,
            'name'               => $this->getTranslateByKeyInResourceFile('name', $resource->translate),
            'description'        => $this->getTranslateByKeyInResourceFile('description', $resource->translate),
            'permissions_count'  => $resource->permissions_count ?? 0,
            'admins_count'       => $resource->admins_count ?? 0,
            'is_active'          => (bool) $resource->is_active,
            'created_at'         => $resource->created_at?->format('Y-m-d'),
            'permissions'        => PermissionsResource::collection($resource->permissions),
            'translate'          => $this->getTranslateByKeyInResourceFile(['name', 'description'], $resource->translate),
        ];
    }
}
