<?php

namespace Modules\Admins\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModelPermissionsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        // Static descriptions for UI as they don't exist in DB yet
        $descriptions = [
            'Users' => 'Manage user accounts',
            'Wallets' => 'Wallet management access',
            'Requests' => 'Handle customer requests',
            'Reports' => 'Generate and view reports',
            'Team Management' => 'Manage team members and roles',
            'Finance' => 'Financial data and transactions',
            'Categories' => 'Manage system categories',
            'Settings' => 'General system configuration',
        ];

        $title = $this->getTranslateByKey('title', $this->resource->translate);

        return [
            'id' => $this->resource->id,
            'title' => $title,
            'description' => $descriptions[$title] ?? '',
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
