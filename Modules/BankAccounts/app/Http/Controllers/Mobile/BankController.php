<?php

namespace Modules\BankAccounts\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use Modules\BankAccounts\Interfaces\BankInterface;
use Modules\BankAccounts\Resources\BankResource;

class BankController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(private BankInterface $repository) {}

    public function index()
    {
        $banks = $this->repository->supported();

        return $this->success(
            __('bankaccounts::messages.banks_retrieved'),
            data: BankResource::collection($banks)
        );
    }
}
