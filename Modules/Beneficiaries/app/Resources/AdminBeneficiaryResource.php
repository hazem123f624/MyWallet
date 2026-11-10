<?php

namespace Modules\Beneficiaries\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Beneficiaries\Models\Beneficiary;
use Modules\Users\Interfaces\UserLookupInterface;

/**
 * @mixin Beneficiary
 */
class AdminBeneficiaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = app(UserLookupInterface::class)->findSummary($this->user_id);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ] : null,
            'name' => $this->name,
            'bank_name' => $this->bank_name,
            'bank_code' => $this->bank_code,
            'account_number' => '****'.$this->account_number_last4,
            'iban' => $this->iban
                ? str_repeat('*', max(0, strlen($this->iban) - 4)).substr($this->iban, -4)
                : null,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
