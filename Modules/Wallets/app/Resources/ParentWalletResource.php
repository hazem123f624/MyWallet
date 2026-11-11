<?php

namespace Modules\Wallets\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\BankAccounts\DTOs\BankAccountSummaryDTO;
use Modules\Wallets\Enums\ParentWalletStatus;
use Modules\Wallets\Models\ParentWallet;

/**
 * @mixin ParentWallet
 */
class ParentWalletResource extends JsonResource
{
    public ?BankAccountSummaryDTO $fundingSource = null;

    /**
     * @var array{count: int, allocated: string}
     */
    public array $summary = ['count' => 0, 'allocated' => '0.00'];

    public $operations = [];

    public function toArray(Request $request): array
    {
        $status = $this->status->value;

        return [
            'id' => $this->id,
            'status' => $status,
            'is_active' => $this->status === ParentWalletStatus::Active,
            'currency' => $this->currency,
            'currency_symbol' => $this->currency_symbol,
            'total_allocated_balance' => $this->total_allocated_balance,
            'available_balance' => $this->available_balance,
            'reserved_balance' => $this->reserved_balance,
            'sub_wallets_count' => $this->summary['count'],
            'allocated_to_sub_wallets' => $this->summary['allocated'],
            'funding_source' => $this->fundingSource ? [
                'id' => $this->fundingSource->id,
                'bank' => [
                    'id' => $this->fundingSource->bankId,
                    'code' => $this->fundingSource->bankCode,
                    'name' => $this->fundingSource->bankName,
                ],
                'account_number' => $this->fundingSource->maskedAccountNumber,
                'is_primary' => $this->fundingSource->isPrimary,
                'status' => $this->fundingSource->status,
                'verified_at' => $this->fundingSource->verifiedAt?->toIso8601String(),
            ] : null,
            'recent_operations' => WalletOperationResource::collection($this->operations),
            'linked_at' => $this->linked_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
