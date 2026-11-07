<?php

namespace Modules\Wallets\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use Illuminate\Auth\AuthenticationException;
use Modules\Wallets\Http\Requests\AllocateSubWalletRequest;
use Modules\Wallets\Http\Requests\RedistributeSubWalletRequest;
use Modules\Wallets\Http\Requests\StoreSubWalletRequest;
use Modules\Wallets\Http\Requests\UpdateSubWalletRequest;
use Modules\Wallets\Resources\SubWalletResource;
use Modules\Wallets\Services\SubWalletService;

class SubWalletController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(private SubWalletService $service) {}

    public function index()
    {
        return $this->success(
            __('wallets::messages.sub_wallets_retrieved'),
            data: SubWalletResource::collection($this->service->list($this->userId()))
        );
    }

    public function store(StoreSubWalletRequest $request)
    {
        $subWallet = $this->service->create($this->userId(), $request->validated());

        return $this->success(
            __('wallets::messages.sub_wallet_created'),
            data: new SubWalletResource($subWallet)
        );
    }

    public function show(int $subWallet)
    {
        $subWallet = $this->service->view($this->userId(), $subWallet);

        return $this->success(
            __('wallets::messages.sub_wallet_retrieved'),
            data: new SubWalletResource($subWallet)
        );
    }

    public function update(UpdateSubWalletRequest $request, int $subWallet)
    {
        $subWallet = $this->service->update($this->userId(), $subWallet, $request->validated());

        return $this->success(
            __('wallets::messages.sub_wallet_updated'),
            data: new SubWalletResource($subWallet)
        );
    }

    public function destroy(int $subWallet)
    {
        $this->service->delete($this->userId(), $subWallet);

        return $this->success(__('wallets::messages.sub_wallet_deleted'), data: []);
    }

    public function allocate(AllocateSubWalletRequest $request, int $subWallet)
    {
        $subWallet = $this->service->allocate(
            $this->userId(),
            $subWallet,
            $request->validated('amount')
        );

        return $this->success(
            __('wallets::messages.sub_wallet_allocated'),
            data: new SubWalletResource($subWallet)
        );
    }

    public function redistribute(RedistributeSubWalletRequest $request, int $subWallet)
    {
        $result = $this->service->redistribute(
            $this->userId(),
            $subWallet,
            (int) $request->validated('destination_sub_wallet_id'),
            $request->validated('amount')
        );

        return $this->success(__('wallets::messages.sub_wallet_redistributed'), data: [
            'source' => new SubWalletResource($result['source']),
            'destination' => new SubWalletResource($result['destination']),
        ]);
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
