<?php

namespace Modules\Beneficiaries\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use Illuminate\Auth\AuthenticationException;
use Modules\Beneficiaries\Http\Requests\StoreBeneficiaryRequest;
use Modules\Beneficiaries\Http\Requests\UpdateBeneficiaryRequest;
use Modules\Beneficiaries\Resources\BeneficiaryResource;
use Modules\Beneficiaries\Services\BeneficiaryService;

class BeneficiaryController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(private BeneficiaryService $service) {}

    public function index()
    {
        return $this->success(
            __('beneficiaries::messages.retrieved'),
            data: BeneficiaryResource::collection($this->service->list($this->userId()))
        );
    }

    public function store(StoreBeneficiaryRequest $request)
    {
        $beneficiary = $this->service->create($this->userId(), $request->validated());

        return $this->success(__('beneficiaries::messages.created'), data: new BeneficiaryResource($beneficiary));
    }

    public function show(int $beneficiary)
    {
        return $this->success(
            __('beneficiaries::messages.retrieved_one'),
            data: new BeneficiaryResource($this->service->view($this->userId(), $beneficiary))
        );
    }

    public function update(UpdateBeneficiaryRequest $request, int $beneficiary)
    {
        $beneficiary = $this->service->update($this->userId(), $beneficiary, $request->validated());

        return $this->success(__('beneficiaries::messages.updated'), data: new BeneficiaryResource($beneficiary));
    }

    public function destroy(int $beneficiary)
    {
        $this->service->delete($this->userId(), $beneficiary);

        return $this->success(__('beneficiaries::messages.deleted'), data: []);
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
