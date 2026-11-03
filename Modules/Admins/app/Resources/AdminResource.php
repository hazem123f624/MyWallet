<?php

namespace Modules\Admins\Resources;

use App\Services\MediaSystem\MediaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Admins\Models\Admins;

/**
 * @mixin Admins
 */
class AdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $dto = $this->resource;

        return [
            'id'            => $dto->id,
            'name'          => $dto->name,
            'email'         => $dto->email,
            'phone'         => $dto->phone,
            'avatar'        => $dto->avatar ? app(MediaService::class)->url($dto->avatar) : null,
            'job_title'     => $dto->job_title,
            'contract_type' => $dto->contract_type,
            'is_active'     => (bool) $dto->is_active,
            'roles'         => RolesResource::collection($dto->roles),
            'created_at'    => $dto->created_at ? Carbon::parse($dto->created_at)->format('Y-m-d') : null,
            'last_login_at' => $dto->last_login_at ? Carbon::parse($dto->last_login_at)->format('Y-m-d h:i A') : null,
        ];
    }
}
