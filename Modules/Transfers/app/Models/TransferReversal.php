<?php

namespace Modules\Transfers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Transfers\Enums\TransferReversalType;

/**
 * @property int $id
 * @property int $transfer_id
 * @property TransferReversalType $type
 * @property string $amount
 * @property string $currency
 * @property string|null $provider_reference
 * @property string|null $reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class TransferReversal extends Model
{
    protected $table = 'transfer_reversals';

    protected $fillable = [
        'transfer_id',
        'type',
        'amount',
        'currency',
        'provider_reference',
        'reason',
    ];

    protected $casts = [
        'id' => 'integer',
        'transfer_id' => 'integer',
        'type' => TransferReversalType::class,
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class, 'transfer_id', 'id');
    }
}
