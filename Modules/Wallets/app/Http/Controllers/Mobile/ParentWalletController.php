<?php

namespace Modules\Wallets\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use Illuminate\Auth\AuthenticationException;
use Modules\Wallets\Http\Requests\UpdateFundingSourceRequest;
use Modules\Wallets\Http\Requests\WalletOperationsRequest;
use Modules\Wallets\Interfaces\SubWalletInterface;
use Modules\Wallets\Interfaces\WalletOperationInterface;
use Modules\Wallets\Models\ParentWallet;
use Modules\Wallets\Resources\ParentWalletResource;
use Modules\Wallets\Resources\WalletOperationResource;
use Modules\Wallets\Services\ParentWalletService;

class ParentWalletController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(
        private ParentWalletService $service,
        private SubWalletInterface $subWallets,
        private WalletOperationInterface $operations,
    ) {}

    public function show()
    {
        [$userId, $countryId] = $this->actor();
        $result = $this->service->viewForUser($userId, $countryId);

        return $this->success(
            __('wallets::messages.wallet_retrieved'),
            data: $this->resource($result['wallet'], $result['funding_source'])
        );
    }

    public function updateFundingSource(UpdateFundingSourceRequest $request)
    {
        [$userId] = $this->actor();
        $wallet = $this->service->linkFundingSource($userId, (int) $request->validated('bank_account_id'));

        return $this->success(
            __('wallets::messages.funding_source_linked'),
            data: $this->resource($wallet, $this->service->fundingSourceFor($wallet))
        );
    }

    public function operations(WalletOperationsRequest $request)
    {
        [$userId] = $this->actor();
        $data = $this->operations->paginateForUser($userId, $request->validated());

        return $this->success(
            __('wallets::messages.operations_retrieved'),
            data: WalletOperationResource::collection($data->items()),
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

    private function resource(ParentWallet $wallet, $fundingSource): ParentWalletResource
    {
        $resource = new ParentWalletResource($wallet);
        $resource->fundingSource = $fundingSource;
        $resource->summary = $this->subWallets->summaryForParent($wallet->id);
        $resource->operations = $this->operations->recentForParent($wallet->id);

        return $resource;
    }

    /**
     * @return array{0: int, 1: int|null}
     */
    private function actor(): array
    {
        $user = auth('sanctum')->user();

        if (! $user) {
            throw new AuthenticationException;
        }

        return [
            (int) $user->getAuthIdentifier(),
            data_get($user, 'country_id') ? (int) data_get($user, 'country_id') : null,
        ];
    }
}
