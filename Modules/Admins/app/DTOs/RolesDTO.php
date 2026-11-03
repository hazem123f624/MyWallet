<?php

namespace Modules\Admins\DTOs;

use App\DTOs\BaseDto;

class RolesDTO extends BaseDto
{
    public function __construct(
        public $id,
        public $translate,
        public $permissions,
        public $adminsList,
        public $admins_count,
        public $permissions_count,
        public $is_active,
        public $created_at,
    ) {}

    public static function fromModel($model): self
    {
        return new self(
            id: $model->id,
            translate: $model->translate,
            permissions: $model->permissions,
            adminsList: $model->adminsList,
            admins_count: $model->admins_count ?? 0,
            permissions_count: $model->permissions_count ?? 0,
            is_active: $model->is_active,
            created_at: $model->created_at,
        );
    }
}
