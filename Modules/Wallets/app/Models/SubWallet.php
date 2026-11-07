<?php

namespace Modules\Wallets\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $parent_wallet_id
 * @property string $name
 * @property string $color
 * @property string $icon
 * @property string $available_balance
 * @property string $reserved_balance
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ParentWallet $parentWallet
 */
class SubWallet extends Model
{
    use SoftDeletes;

    protected $table = 'sub_wallets';

    protected $fillable = [
        'parent_wallet_id',
        'name',
        'color',
        'icon',
        'available_balance',
        'reserved_balance',
    ];

    protected $casts = [
        'id' => 'integer',
        'parent_wallet_id' => 'integer',
        'available_balance' => 'decimal:2',
        'reserved_balance' => 'decimal:2',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function parentWallet(): BelongsTo
    {
        return $this->belongsTo(ParentWallet::class, 'parent_wallet_id', 'id');
    }

    public function scopeOwnedByParent(Builder $query, int $parentWalletId): Builder
    {
        return $query->where('parent_wallet_id', $parentWalletId);
    }
}
