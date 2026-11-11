<?php

namespace Modules\Transfers\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Transfers\Models\TransferReversal;

/**
 * @mixin TransferReversal
 */
class TransferReversalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'provider_reference' => $this->provider_reference,
            'reason' => $this->reason,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
