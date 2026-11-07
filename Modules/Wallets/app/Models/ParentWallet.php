<?php

namespace Modules\Wallets\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Modules\Wallets\Enums\ParentWalletStatus;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $bank_account_id
 * @property string $currency
 * @property string|null $currency_symbol
 * @property string $total_allocated_balance
 * @property string $available_balance
 * @property string $reserved_balance
 * @property ParentWalletStatus $status
 * @property Carbon|null $linked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ParentWallet extends Model
{
    protected $table = 'parent_wallets';

    protected $fillable = [
        'user_id',
        'bank_account_id',
        'currency',
        'currency_symbol',
        'total_allocated_balance',
        'available_balance',
        'reserved_balance',
        'status',
        'linked_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'bank_account_id' => 'integer',
        'total_allocated_balance' => 'decimal:2',
        'available_balance' => 'decimal:2',
        'reserved_balance' => 'decimal:2',
        'status' => ParentWalletStatus::class,
        'linked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function subWallets()
    {
        return $this->hasMany(SubWallet::class, 'parent_wallet_id', 'id');
    }
}
