<?php

namespace Modules\Transfers\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use Illuminate\Auth\AuthenticationException;
use Modules\Transfers\Http\Requests\StoreTransferRequest;
use Modules\Transfers\Http\Requests\TransferHistoryRequest;
use Modules\Transfers\Resources\TransferResource;
use Modules\Transfers\Services\TransferService;

class TransferController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(private TransferService $service) {}

    public function index()
    {
        return $this->success(
            __('transfers::messages.retrieved'),
            data: TransferResource::collection($this->service->listForUser($this->userId()))
        );
    }

    public function history(TransferHistoryRequest $request)
    {
        $data = $this->service->historyForUser($this->userId(), $request->validated());

        return $this->success(
            __('transfers::messages.retrieved'),
            data: TransferResource::collection($data->items()),
            appends: [
                'pagination' => [
                    'per_page' => $data->perPage(),
                    'current_page' => $data->currentPage(),
                    'total' => $data->total(),
                    'last_page' => $data->lastPage(),
                ],
            ]
        );
    }

    public function store(StoreTransferRequest $request)
    {
        $transfer = $this->service->create($this->userId(), $request->validated());

        return $this->success(
            __('transfers::messages.processed'),
            data: new TransferResource($transfer)
        );
    }

    public function show(int $transfer)
    {
        return $this->success(
            __('transfers::messages.retrieved_one'),
            data: new TransferResource($this->service->viewForUser($this->userId(), $transfer))
        );
    }

    public function refresh(int $transfer)
    {
        $transfer = $this->service->refreshForUser($this->userId(), $transfer);

        return $this->success(
            __('transfers::messages.status_checked'),
            data: new TransferResource($transfer)
        );
    }

    public function limits()
    {
        return $this->success(
            __('transfers::messages.limits_retrieved'),
            data: $this->service->limitsForUser($this->userId())
        );
    }

    private function userId(): int
    {
        $user = auth('sanctum')->user();

        if (! $user) {
            throw new AuthenticationException;
        }

        return (int) $user->getAuthIdentifier();
    }
}
