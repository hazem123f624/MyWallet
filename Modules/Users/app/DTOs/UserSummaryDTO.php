<?php

namespace Modules\Users\DTOs;

class UserSummaryDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $email,
        public ?string $phone,
    ) {}
}
