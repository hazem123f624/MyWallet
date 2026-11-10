<?php

namespace Modules\Transfers\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Transfers\Models\Transfer;
use Modules\Users\Interfaces\UserLookupInterface;

/**
 * @mixin Transfer
 */
class AdminTransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status->value;
        $user = app(UserLookupInterface::class)->findSummary($this->user_id);

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'client_reference' => $this->client_reference,
            'user_id' => $this->user_id,
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ] : null,
            'status' => $status,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'sub_wallet' => [
                'id' => $this->sub_wallet_id,
                'name' => $this->sub_wallet_name,
            ],
            'beneficiary' => [
                'id' => $this->beneficiary_id,
                'name' => $this->beneficiary_name,
                'bank_name' => $this->beneficiary_bank_name,
                'account_number' => '****'.$this->beneficiary_account_last4,
            ],
            'provider_name' => $this->provider_name,
            'provider_reference' => $this->provider_reference,
            'failure_reason' => $this->failure_reason,
            'note' => $this->note,
            'requested_at' => $this->requested_at?->toIso8601String(),
            'processed_at' => $this->processed_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'reversed_at' => $this->reversed_at?->toIso8601String(),
            'refunded_at' => $this->refunded_at?->toIso8601String(),
            'events' => TransferEventResource::collection($this->whenLoaded('events')),
            'reversal' => $this->whenLoaded('reversal', fn () => $this->reversal ? new TransferReversalResource($this->reversal) : null),
        ];
    }
}
