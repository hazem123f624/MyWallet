<?php

namespace Modules\Admins\DTOs;

use App\DTOs\BaseDto;

class PermissionsDTO extends BaseDto
{
    public function __construct(
        public $id,
        public $name,
        public $translate,
        public $created_at,
    ) {}

    public static function fromModel($model): self
    {
        return new self(
            id: $model->id,
            name: $model->name,
            translate: PermissionsTranslateDTO::collection($model->translate),
            created_at: date('Y-m-d H:i:s', strtotime($model->created_at)),
        );
    }
}
