<?php

namespace Modules\Admins\DTOs;

use App\DTOs\BaseDto;

class AdminsDTO extends BaseDto
{
    public function __construct(
        public $id,
        public $name,
        public $email,
        public $phone,
        public $password,
        public $avatar,
        public $job_title,
        public $contract_type,
        public $is_active,
        public $otp_pin,
        public $otp_token,
        public $otp_expired_at,
        public $last_login_at,
        public $roles,
        public $created_at
    ) {}

    public static function fromModel($model): self
    {
        return new self(
            id: $model->id,
            name: $model->name,
            email: $model->email,
            phone: $model->phone,
            password: $model->password,
            avatar: $model->avatar,
            job_title: $model->job_title,
            contract_type: $model->contract_type,
            is_active: $model->is_active,
            otp_pin: $model->otp_pin,
            otp_token: $model->otp_token,
            otp_expired_at: $model->otp_expired_at,
            last_login_at: $model->last_login_at,
            roles: $model->roles,
            created_at: $model->created_at,
        );
    }
}
