<?php

namespace Modules\Transfers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Transfers\Enums\TransferEventType;

/**
 * @property int $id
 * @property int $transfer_id
 * @property TransferEventType $type
 * @property string|null $from_status
 * @property string|null $to_status
 * @property string|null $provider_reference
 * @property string|null $message
 * @property array<string,mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class TransferEvent extends Model
{
    protected $table = 'transfer_events';

    protected $fillable = [
        'transfer_id',
        'type',
        'from_status',
        'to_status',
        'provider_reference',
        'message',
        'metadata',
    ];

    protected $casts = [
        'id' => 'integer',
        'transfer_id' => 'integer',
        'type' => TransferEventType::class,
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class, 'transfer_id', 'id');
    }
}
