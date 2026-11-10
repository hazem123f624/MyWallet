<?php

namespace Modules\Beneficiaries\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use Modules\Beneficiaries\Resources\AdminBeneficiaryResource;
use Modules\Beneficiaries\Services\BeneficiaryService;

class BeneficiaryController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(private BeneficiaryService $service) {}

    public function index()
    {
        $data = $this->service->adminList(request()->all());

        return $this->success(
            __('beneficiaries::messages.retrieved'),
            data: AdminBeneficiaryResource::collection($data->items()),
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

    public function show(int $beneficiary)
    {
        return $this->success(
            __('beneficiaries::messages.retrieved_one'),
            data: new AdminBeneficiaryResource($this->service->adminView($beneficiary))
        );
    }
}
