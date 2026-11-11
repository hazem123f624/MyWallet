<?php

namespace Modules\Wallets\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Wallets\Models\SubWallet;
use Modules\Wallets\Support\Money;

/**
 * @mixin SubWallet
 */
class SubWalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'color' => $this->color,
            'icon' => $this->icon,
            'currency' => $this->parentWallet->currency,
            'currency_symbol' => $this->parentWallet->currency_symbol,
            'available_balance' => $this->available_balance,
            'reserved_balance' => $this->reserved_balance,
            'total_balance' => Money::add($this->available_balance, $this->reserved_balance),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
