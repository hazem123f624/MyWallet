<?php

namespace Modules\Admins\DTOs;

use App\DTOs\BaseDto;

class ModelsDTO extends BaseDto
{
    public function __construct(
        public $id,
        public $name,
        public $translate,
        public $permissions,
        public $created_at
    ) {}

    public static function fromModel($model): self
    {
        return new self(
            id: $model->id,
            name: $model->name,
            translate: ModelsTranslateDTO::collection($model->translate),
            permissions: PermissionsDTO::collection($model->permissions),
            created_at: date('Y-m-d H:i:s', strtotime($model->created_at)),
        );
    }
}
