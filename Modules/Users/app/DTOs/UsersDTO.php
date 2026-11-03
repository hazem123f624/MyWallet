<?php

namespace Modules\Users\DTOs;

use App\DTOs\BaseDto;

class UsersDTO extends BaseDto
{
    public function __construct(
        public $id,
        public $name,
        public $email,
        public $phone,
        public $country_id,
        public $avatar,
        public $postal_code,
        public $main_address,
        public $is_active,
        public $last_login_at,
        public $created_at,
        public $token = null,
        public $expires_in = null,
        public $token_type = 'Bearer'
    ) {}

    public static function fromModel($model): self
    {
        return new self(
            id: $model->id,
            name: $model->name,
            email: $model->email,
            phone: $model->phone,
            country_id: $model->country_id ?? null,
            avatar: $model->avatar,
            postal_code: $model->postal_code,
            main_address: $model->main_address,
            is_active: (bool) $model->is_active,
            last_login_at: $model->last_login_at,
            created_at: $model->created_at,
        );
    }

    public function withToken($token, $tokenType = 'Bearer', $expiresIn = null): self
    {
        $this->token = $token;
        $this->token_type = $tokenType;
        $this->expires_in = $expiresIn;

        return $this;
    }
}
