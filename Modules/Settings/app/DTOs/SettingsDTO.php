<?php

namespace Modules\Settings\DTOs;

use App\DTOs\BaseDto;

class SettingsDTO extends BaseDto
{
    public function __construct(
        public $id,
        public $type,
        public $name_ar,
        public $name_en,
        public $data,
        public $setting_id,
        public $is_active,
        public $created_at
    ) {}

    public static function fromModel($model)
    {
        return new self(
            id: $model->id,
            type: $model->type,
            name_ar: $model->name_ar,
            name_en: $model->name_en,
            data: $model->data,
            setting_id: $model->setting_id,
            is_active: $model->is_active,
            created_at: date('Y-m-d H:i:s', strtotime($model->created_at)),
        );
    }

    public static function fromArray($model)
    {
        return [
            'id' => $model->id,
            'type' => $model->type,
            'name_ar' => $model->name_ar,
            'name_en' => $model->name_en,
            'data' => $model->data,
            'setting_id' => $model->setting_id,
            'isActive' => $model->is_active,
            'created_at' => date('Y-m-d H:i:s', strtotime($model->created_at)),
        ];
    }
}
