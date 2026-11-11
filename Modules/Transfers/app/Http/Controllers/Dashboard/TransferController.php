<?php

namespace Modules\Transfers\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use Modules\Transfers\Resources\AdminTransferResource;
use Modules\Transfers\Services\TransferService;

class TransferController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(private TransferService $service) {}

    public function index()
    {
        $data = $this->service->adminList(request()->all());

        return $this->success(
            __('transfers::messages.retrieved'),
            data: AdminTransferResource::collection($data->items()),
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

    public function show(int $transfer)
    {
        return $this->success(
            __('transfers::messages.retrieved_one'),
            data: new AdminTransferResource($this->service->adminView($transfer))
        );
    }

    public function sync(int $transfer)
    {
        $transfer = $this->service->syncAdminTransfer($transfer);

        return $this->success(
            __('transfers::messages.status_checked'),
            data: new AdminTransferResource($transfer)
        );
    }

    public function statistics()
    {
        return $this->success(
            __('transfers::messages.statistics_retrieved'),
            data: $this->service->statistics()
        );
    }
}
