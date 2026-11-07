<?php

namespace Modules\Users\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserAccountVerified
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $userId,
        public ?int $countryId = null,
    ) {}
}
