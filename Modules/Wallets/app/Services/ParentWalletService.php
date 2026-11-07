<?php

namespace Modules\Wallets\Services;

use App\Services\CountryService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\BankAccounts\DTOs\BankAccountSummaryDTO;
use Modules\BankAccounts\Interfaces\BankAccountLookupInterface;
use Modules\Wallets\Enums\ParentWalletStatus;
use Modules\Wallets\Interfaces\ParentWalletInterface;
use Modules\Wallets\Models\ParentWallet;

class ParentWalletService
{
    public function __construct(
        private ParentWalletInterface $repository,
        private BankAccountLookupInterface $bankAccounts,
    ) {}

    public function ensureForUser(int $userId, ?int $countryId = null): ParentWallet
    {
        $existing = $this->repository->findForUser($userId);

        if ($existing) {
            return $existing;
        }

        [$currency, $symbol] = $this->currencyFor($countryId);

        try {
            return $this->repository->create([
                'user_id' => $userId,
                'bank_account_id' => null,
                'currency' => $currency,
                'currency_symbol' => $symbol,
                'total_allocated_balance' => 0,
                'available_balance' => 0,
                'reserved_balance' => 0,
                'status' => ParentWalletStatus::Unlinked,
                'linked_at' => null,
            ]);
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23000') {
                return $this->repository->findForUser($userId)
                    ?? throw $exception;
            }

            throw $exception;
        }
    }

    /**
     * @return array{wallet: ParentWallet, funding_source: BankAccountSummaryDTO|null}
     */
    public function viewForUser(int $userId, ?int $countryId = null): array
    {
        $wallet = $this->ensureForUser($userId, $countryId);
        $wallet = $this->syncFundingSource($userId);

        return [
            'wallet' => $wallet,
            'funding_source' => $this->fundingSourceFor($wallet),
        ];
    }

    public function linkFundingSource(int $userId, int $bankAccountId): ParentWallet
    {
        $this->ensureForUser($userId);

        return DB::transaction(function () use ($userId, $bankAccountId): ParentWallet {
            $wallet = $this->repository->findForUser($userId, true)
                ?? throw ValidationException::withMessages([
                    'wallet' => __('wallets::messages.wallet_not_found'),
                ]);

            $account = $this->bankAccounts->findForUser($userId, $bankAccountId);

            if (! $account) {
                throw ValidationException::withMessages([
                    'bank_account_id' => __('wallets::messages.bank_account_not_found'),
                ]);
            }

            if ($account->status !== 'verified') {
                throw ValidationException::withMessages([
                    'bank_account_id' => __('wallets::messages.bank_account_not_verified'),
                ]);
            }

            if (! $account->isPrimary) {
                throw ValidationException::withMessages([
                    'bank_account_id' => __('wallets::messages.bank_account_not_primary'),
                ]);
            }

            if ($wallet->bank_account_id === $account->id && $wallet->status === ParentWalletStatus::Active) {
                return $wallet;
            }

            return $this->repository->update($wallet, [
                'bank_account_id' => $account->id,
                'status' => ParentWalletStatus::Active,
                'linked_at' => now(),
            ]);
        });
    }

    public function syncFundingSource(int $userId): ParentWallet
    {
        $this->ensureForUser($userId);

        return DB::transaction(function () use ($userId): ParentWallet {
            $wallet = $this->repository->findForUser($userId, true)
                ?? throw ValidationException::withMessages([
                    'wallet' => __('wallets::messages.wallet_not_found'),
                ]);

            $primary = $this->bankAccounts->findPrimaryVerifiedForUser($userId);

            if (! $primary) {
                if ($wallet->bank_account_id === null && $wallet->status === ParentWalletStatus::Unlinked) {
                    return $wallet;
                }

                return $this->repository->update($wallet, [
                    'bank_account_id' => null,
                    'status' => ParentWalletStatus::Unlinked,
                    'linked_at' => null,
                ]);
            }

            if ($wallet->bank_account_id === $primary->id && $wallet->status === ParentWalletStatus::Active) {
                return $wallet;
            }

            return $this->repository->update($wallet, [
                'bank_account_id' => $primary->id,
                'status' => ParentWalletStatus::Active,
                'linked_at' => $wallet->linked_at ?? now(),
            ]);
        });
    }

    public function fundingSourceFor(ParentWallet $wallet): ?BankAccountSummaryDTO
    {
        if ($wallet->bank_account_id === null) {
            return null;
        }

        return $this->bankAccounts->findById($wallet->bank_account_id);
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    private function currencyFor(?int $countryId): array
    {
        if ($countryId) {
            $country = app(CountryService::class)->getCountryById($countryId);
            $data = [];

            if ($country) {
                $data = $country->data ?? [];
            }

            if (! empty($data['currency'])) {
                return [$data['currency'], $data['currency_symbol'] ?? null];
            }
        }

        return [
            config('wallets.default_currency', 'EGP'),
            config('wallets.default_currency_symbol', 'E£'),
        ];
    }
}
