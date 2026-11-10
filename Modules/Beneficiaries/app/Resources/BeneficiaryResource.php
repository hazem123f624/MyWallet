<?php

namespace Modules\Beneficiaries\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Beneficiaries\Models\Beneficiary;

/**
 * @mixin Beneficiary
 */
class BeneficiaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'bank_name' => $this->bank_name,
            'bank_code' => $this->bank_code,
            'account_number' => '****'.$this->account_number_last4,
            'iban' => $this->iban
                ? str_repeat('*', max(0, strlen($this->iban) - 4)).substr($this->iban, -4)
                : null,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
