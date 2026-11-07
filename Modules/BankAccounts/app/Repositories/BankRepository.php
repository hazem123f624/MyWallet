<?php

namespace Modules\BankAccounts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\BankAccounts\Interfaces\BankInterface;
use Modules\BankAccounts\Models\Bank;

class BankRepository implements BankInterface
{
    public function __construct(private Bank $model) {}

    public function supported(): Collection
    {
        return $this->model
            ->newQuery()
            ->supported()
            ->ordered()
            ->get();
    }
}
