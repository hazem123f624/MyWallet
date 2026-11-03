<?php

namespace App\Services\Messages;

interface MessageServiceInterface
{
    public function send(array $data): void;
}
