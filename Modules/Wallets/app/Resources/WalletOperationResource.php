<?php

namespace Modules\Wallets\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Wallets\Models\WalletOperation;

/**
 * @mixin WalletOperation
 */
class WalletOperationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $type = $this->type->value;

        return [
            'id' => $this->id,
            'type' => $type,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'description' => $this->description,
            'from_sub_wallet' => $this->fromSubWallet ? [
                'id' => $this->fromSubWallet->id,
                'name' => $this->fromSubWallet->name,
            ] : null,
            'to_sub_wallet' => $this->toSubWallet ? [
                'id' => $this->toSubWallet->id,
                'name' => $this->toSubWallet->name,
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
