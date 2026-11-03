<?php

namespace Modules\Admins\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Admins\Models\Admins;

/**
 * @mixin Admins
 */
class AdminLoginResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $roles = $this->roles ?? collect();

        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'email'        => $this->email,
            'phone'        => $this->phone,
            'avatar'       => $this->avatar,
            'token'        => $this->token ?? null,
            'expires_in'   => $this->expires_in ?? null,
            'token_type'   => $this->token_type ?? 'Bearer',
            'roles'        => $roles->map(function ($role) {
                $translate = $role->translate ?? ($role->role->translate ?? null);

                return [
                    'id'   => $role->id,
                    'name' => $this->getTranslateByKey('name', $translate),
                ];
            })->values(),
            'permissions'   => $roles->flatMap(function ($role) {
                return collect($role->permissions)->map(function ($permission) {
                    return $permission->name;
                });
            })->values(),
            'created_at' => $this->created_at?->format('Y-m-d'),
        ];
    }

    protected function getTranslateByKey($key, $object)
    {
        if (! $object) {
            return '';
        }
        $value = '';
        $object = $object->where('lang', app()->getLocale())->first();
        if ($object) {
            $value = $object->$key;
        }

        return $value;
    }
}
