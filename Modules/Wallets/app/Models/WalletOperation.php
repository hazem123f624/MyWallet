<?php

namespace Modules\Wallets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Wallets\Enums\WalletOperationType;

/**
 * @property int $id
 * @property int $user_id
 * @property int $parent_wallet_id
 * @property int|null $from_sub_wallet_id
 * @property int|null $to_sub_wallet_id
 * @property WalletOperationType $type
 * @property string $amount
 * @property string $currency
 * @property string|null $description
 * @property array<string,mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SubWallet|null $fromSubWallet
 * @property-read SubWallet|null $toSubWallet
 */
class WalletOperation extends Model
{
    protected $table = 'wallet_operations';

    protected $fillable = [
        'user_id',
        'parent_wallet_id',
        'from_sub_wallet_id',
        'to_sub_wallet_id',
        'type',
        'amount',
        'currency',
        'description',
        'metadata',
    ];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'parent_wallet_id' => 'integer',
        'from_sub_wallet_id' => 'integer',
        'to_sub_wallet_id' => 'integer',
        'type' => WalletOperationType::class,
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function fromSubWallet(): BelongsTo
    {
        return $this->belongsTo(SubWallet::class, 'from_sub_wallet_id', 'id')->withTrashed();
    }

    public function toSubWallet(): BelongsTo
    {
        return $this->belongsTo(SubWallet::class, 'to_sub_wallet_id', 'id')->withTrashed();
    }
}
