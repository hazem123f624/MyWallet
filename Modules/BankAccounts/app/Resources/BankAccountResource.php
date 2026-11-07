<?php

namespace Modules\BankAccounts\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\BankAccounts\Enums\BankAccountStatus;
use Modules\BankAccounts\Models\BankAccount;

/**
 * @mixin BankAccount
 */
class BankAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $last4 = (string) ($this->account_number_last4 ?? '');
        $status = $this->status->value;

        return [
            'id' => $this->id,
            'bank' => new BankResource($this->bank),
            'account_holder_name' => $this->account_holder_name,
            'account_number' => $last4 !== '' ? '****'.$last4 : null,
            'iban' => $this->iban ? str_repeat('*', max(0, strlen($this->iban) - 4)).substr($this->iban, -4) : null,
            'is_primary' => (bool) $this->is_primary,
            'is_verified' => $this->status === BankAccountStatus::Verified,
            'status' => $status,
            'verified_at' => $this->verified_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
