<?php

namespace Modules\Admins\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Admins\Models\Admins;

/**
 * @mixin Admins
 */
class RoleAdminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->resource->id,
            'name'          => $this->resource->name,
            'email'         => $this->resource->email,
            'avatar'        => $this->resource->avatar,
            'created_at'    => $this->resource->created_at ? Carbon::parse($this->resource->created_at)->format('Y-m-d') : null,
        ];
    }
}
