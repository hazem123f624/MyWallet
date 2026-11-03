<?php

namespace Modules\Admins\DTOs;

use App\DTOs\BaseDto;

class PermissionsTranslateDTO extends BaseDto
{
    public function __construct(
        public $id,
        public $title,
        public $lang
    ) {}

    public static function fromModel($model): self
    {
        return new self(
            id: $model->id,
            title: $model->title,
            lang: $model->lang,
        );
    }
}
