<?php

namespace Modules\BankAccounts\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use Illuminate\Auth\AuthenticationException;
use Modules\BankAccounts\Http\Requests\StoreBankAccountRequest;
use Modules\BankAccounts\Http\Requests\VerifyBankAccountRequest;
use Modules\BankAccounts\Resources\BankAccountResource;
use Modules\BankAccounts\Services\BankAccountService;

class BankAccountController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(private BankAccountService $service) {}

    public function index()
    {
        [$userId] = $this->actor();

        return $this->success(
            __('bankaccounts::messages.accounts_retrieved'),
            data: BankAccountResource::collection($this->service->list($userId))
        );
    }

    public function store(StoreBankAccountRequest $request)
    {
        [$userId, $phone] = $this->actor();

        $result = $this->service->link($userId, $phone, $request->validated());

        return $this->success(__('bankaccounts::messages.account_linked'), data: [
            'account' => new BankAccountResource($result['account']),
            'otp' => config('app.env') !== 'production' ? $result['otp'] : null,
        ]);
    }

    public function resendOtp(int $bankAccount)
    {
        [$userId, $phone] = $this->actor();

        $otp = $this->service->resendOtp($userId, $phone, $bankAccount);

        return $this->success(__('bankaccounts::messages.verification_otp_sent'), data: [
            'otp' => config('app.env') !== 'production' ? $otp : null,
        ]);
    }

    public function verify(VerifyBankAccountRequest $request, int $bankAccount)
    {
        [$userId, $phone] = $this->actor();

        $account = $this->service->verify($userId, $phone, $bankAccount, $request->validated('otp'));

        return $this->success(
            __('bankaccounts::messages.account_verified'),
            data: new BankAccountResource($account)
        );
    }

    public function setPrimary(int $bankAccount)
    {
        [$userId] = $this->actor();

        $account = $this->service->setPrimary($userId, $bankAccount);

        return $this->success(
            __('bankaccounts::messages.primary_account_updated'),
            data: new BankAccountResource($account)
        );
    }

    public function destroy(int $bankAccount)
    {
        [$userId] = $this->actor();

        $this->service->unlink($userId, $bankAccount);

        return $this->success(__('bankaccounts::messages.account_unlinked'), data: []);
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function actor(): array
    {
        $user = auth('sanctum')->user();

        if (! $user) {
            throw new AuthenticationException;
        }

        return [(int) $user->getAuthIdentifier(), (string) ($user->phone ?? '')];
    }
}
