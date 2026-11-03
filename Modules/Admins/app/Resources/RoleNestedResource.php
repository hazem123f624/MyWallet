<?php

namespace Modules\Admins\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleNestedResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $translation = $this->resource->translate
            ?->where('lang', app()->getLocale())
            ->first();

        return [
            'id' => $this->resource->id,
            'name' => $translation->name ?? '',
            'description' => $translation->description ?? '',
            'is_active' => (bool) $this->resource->is_active,
            'created_at' => $this->resource->created_at?->format('Y-m-d'),
        ];
    }
}
