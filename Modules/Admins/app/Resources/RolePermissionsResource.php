<?php

namespace Modules\Admins\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RolePermissionsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $perm = $this->resource;

        $translate = $perm->translate ?? ($perm['translate'] ?? null);
        $translation = collect($translate)->firstWhere('lang', app()->getLocale())
            ?? (is_array($translate) ? ($translate[0] ?? null) : null);

        $name = $translation['title'] ?? ($translation->title ?? ($perm->name ?? ($perm['name'] ?? null)));

        return [
            'id' => $perm->id ?? ($perm['id'] ?? null),
            'name' => $name,
            'key' => $perm->key ?? ($perm['key'] ?? null),
            ];
    }
}
