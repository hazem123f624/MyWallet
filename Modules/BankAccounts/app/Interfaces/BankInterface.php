<?php

namespace Modules\BankAccounts\Interfaces;

use Illuminate\Database\Eloquent\Collection;

interface BankInterface
{
    public function supported(): Collection;
}
