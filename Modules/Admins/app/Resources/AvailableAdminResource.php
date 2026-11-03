<?php

namespace Modules\Admins\Resources;

use App\Services\MediaSystem\MediaService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Admins\Models\Admins;

/**
 * @mixin Admins
 */
class AvailableAdminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->resource->id,
            'name'      => $this->resource->name,
            'job_title' => $this->resource->job_title,
            'avatar'    => $this->resource->avatar ? app(MediaService::class)->url($this->resource->avatar) : null,
        ];
    }
}
