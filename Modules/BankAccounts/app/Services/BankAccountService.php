<?php

namespace Modules\BankAccounts\Services;

use App\Services\OtpService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\BankAccounts\Enums\BankAccountStatus;
use Modules\BankAccounts\Events\BankAccountPrimaryChanged;
use Modules\BankAccounts\Events\BankAccountUnlinked;
use Modules\BankAccounts\Events\BankAccountVerified;
use Modules\BankAccounts\Interfaces\BankAccountInterface;
use Modules\BankAccounts\Models\Bank;
use Modules\BankAccounts\Models\BankAccount;

class BankAccountService
{
    public function __construct(private BankAccountInterface $repository) {}

    public function list(int $userId): Collection
    {
        return $this->repository->listForUser($userId);
    }

    /**
     * Link a bank account and send the ownership-verification code.
     *
     * @return array{account: BankAccount, otp: int}
     */
    public function link(int $userId, string $phone, array $data): array
    {
        try {
            return DB::transaction(function () use ($userId, $phone, $data): array {
                $bank = Bank::query()
                    ->supported()
                    ->lockForUpdate()
                    ->find($data['bank_id']);

                if (! $bank) {
                    throw ValidationException::withMessages([
                        'bank_id' => __('bankaccounts::messages.bank_not_supported'),
                    ]);
                }

                $accountNumber = preg_replace('/\D+/', '', (string) $data['account_number']) ?? '';
                $hash = hash('sha256', $accountNumber);

                if ($this->repository->findByOwnerBankHash($userId, (int) $bank->id, $hash, true)) {
                    throw ValidationException::withMessages([
                        'account_number' => __('bankaccounts::messages.account_already_linked'),
                    ]);
                }

                $account = $this->repository->create([
                    'user_id' => $userId,
                    'bank_id' => $bank->id,
                    'account_holder_name' => $data['account_holder_name'],
                    'account_number' => $accountNumber,
                    'account_number_hash' => $hash,
                    'account_number_last4' => substr($accountNumber, -4),
                    'iban' => $data['iban'] ?? null,
                    'is_primary' => ! $this->repository->hasPrimary($userId),
                    'status' => BankAccountStatus::Pending,
                    'verified_at' => null,
                ]);

                $otp = app(OtpService::class)->generateByCache(
                    identifier: $this->otpIdentifier($userId, $phone),
                    type: $this->otpType($account->id)
                );

                return ['account' => $account->load('bank'), 'otp' => $otp];
            });
        } catch (QueryException $exception) {
            if ($this->isDuplicateAccount($exception)) {
                throw ValidationException::withMessages([
                    'account_number' => __('bankaccounts::messages.account_already_linked'),
                ]);
            }

            throw $exception;
        }
    }

    public function resendOtp(int $userId, string $phone, int $accountId): int
    {
        $account = $this->findOwnedOrFail($userId, $accountId);

        if ($account->status === BankAccountStatus::Verified) {
            throw ValidationException::withMessages([
                'otp' => __('bankaccounts::messages.account_already_verified'),
            ]);
        }

        return app(OtpService::class)->generateByCache(
            identifier: $this->otpIdentifier($userId, $phone),
            type: $this->otpType($account->id)
        );
    }

    public function verify(int $userId, string $phone, int $accountId, string $otp): BankAccount
    {
        $account = DB::transaction(function () use ($userId, $phone, $accountId, $otp): BankAccount {
            $account = $this->findOwnedOrFail($userId, $accountId, true);

            if ($account->status === BankAccountStatus::Verified) {
                throw ValidationException::withMessages([
                    'otp' => __('bankaccounts::messages.account_already_verified'),
                ]);
            }

            app(OtpService::class)->verifyByCache(
                identifier: $this->otpIdentifier($userId, $phone),
                code: $otp,
                type: $this->otpType($account->id)
            );

            $this->repository->update($account, [
                'status' => BankAccountStatus::Verified,
                'verified_at' => now(),
                'is_primary' => ! $this->repository->hasPrimary($userId, $account->id),
            ]);

            return $account->refresh()->load('bank');
        });

        event(new BankAccountVerified($userId, $account->id));

        return $account;
    }

    public function setPrimary(int $userId, int $accountId): BankAccount
    {
        $account = DB::transaction(function () use ($userId, $accountId): BankAccount {
            $account = $this->findOwnedOrFail($userId, $accountId, true);

            if ($account->status !== BankAccountStatus::Verified) {
                throw ValidationException::withMessages([
                    'account' => __('bankaccounts::messages.account_not_verified'),
                ]);
            }

            if ($account->is_primary) {
                return $account->load('bank');
            }

            $this->repository->clearPrimary($userId, $accountId);
            $this->repository->update($account, ['is_primary' => true]);

            return $account->load('bank');
        });

        event(new BankAccountPrimaryChanged($userId, $account->id));

        return $account;
    }

    public function unlink(int $userId, int $accountId): void
    {
        DB::transaction(function () use ($userId, $accountId): void {
            $account = $this->findOwnedOrFail($userId, $accountId, true);
            $wasPrimary = $account->is_primary;

            $this->repository->delete($account);

            if (! $wasPrimary) {
                return;
            }

            $next = $this->repository->firstRemainingForUser($userId);

            if ($next) {
                $this->repository->update($next, ['is_primary' => true]);
            }
        });

        event(new BankAccountUnlinked($userId, $accountId));
    }

    private function findOwnedOrFail(int $userId, int $accountId, bool $lock = false): BankAccount
    {
        $account = $this->repository->findForUser($userId, $accountId, $lock);

        if (! $account) {
            throw (new ModelNotFoundException)
                ->setModel(BankAccount::class, [$accountId]);
        }

        return $account;
    }

    private function otpType(int $accountId): string
    {
        return 'bank_account_verification_'.$accountId;
    }

    private function otpIdentifier(int $userId, string $phone): string
    {
        return $phone !== '' ? $phone : 'user:'.$userId;
    }

    private function isDuplicateAccount(QueryException $exception): bool
    {
        return (string) $exception->getCode() === '23000'
            && str_contains($exception->getMessage(), 'bank_accounts_owner_account_unique');
    }
}
