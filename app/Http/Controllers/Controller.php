<?php

namespace App\Http\Controllers;

use App\Http\Traits\DatabaseStatementsInitTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use DatabaseStatementsInitTrait;
    use AuthorizesRequests;
    protected function handelRequests($requests = [], $exceptLike = [], $dates = [], $orders = [])
    {
        return $this->SetStatementDB(request: [], filters: $requests, exceptLike: $exceptLike, dates: $dates, orders: $orders);
    }
}
