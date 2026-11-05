<?php

namespace Modules\Users\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use Illuminate\Auth\AuthenticationException;
use Modules\Users\Http\Requests\ChangeTransactionPinRequest;
use Modules\Users\Http\Requests\ResetTransactionPinRequest;
use Modules\Users\Http\Requests\StoreTransactionPinRequest;
use Modules\Users\Http\Requests\VerifyTransactionPinRequest;
use Modules\Users\Models\User;
use Modules\Users\Services\TransactionPinService;

class TransactionPinController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(private TransactionPinService $transactionPinService) {}

    public function set(StoreTransactionPinRequest $request)
    {
        $this->transactionPinService->set(
            $this->user(),
            $request->validated('transaction_pin')
        );

        return $this->success(__('users::messages.transaction_pin_set'));
    }

    public function change(ChangeTransactionPinRequest $request)
    {
        $validated = $request->validated();

        $this->transactionPinService->change(
            $this->user(),
            $validated['current_transaction_pin'],
            $validated['transaction_pin']
        );

        return $this->success(__('users::messages.transaction_pin_changed'));
    }

    public function sendResetOtp()
    {
        $code = $this->transactionPinService->sendResetOtp($this->user());

        return $this->success(__('users::messages.transaction_pin_reset_otp_sent'), data: [
            'otp' => config('app.env') !== 'production' ? $code : null,
        ]);
    }

    public function reset(ResetTransactionPinRequest $request)
    {
        $validated = $request->validated();

        $this->transactionPinService->resetWithOtp(
            $this->user(),
            $validated['otp'],
            $validated['transaction_pin']
        );

        return $this->success(__('users::messages.transaction_pin_reset'));
    }

    public function verify(VerifyTransactionPinRequest $request)
    {
        $this->transactionPinService->verify(
            $this->user(),
            $request->validated('transaction_pin')
        );

        return $this->success(__('users::messages.transaction_pin_verified'));
    }

    private function user(): User
    {
        $user = auth('sanctum')->user();

        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        return $user;
    }
}
