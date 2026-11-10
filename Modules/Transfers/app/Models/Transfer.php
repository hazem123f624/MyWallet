<?php

namespace Modules\Transfers\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Modules\Transfers\Enums\TransferStatus;

/**
 * @property int $id
 * @property int $user_id
 * @property int $parent_wallet_id
 * @property int $sub_wallet_id
 * @property int|null $beneficiary_id
 * @property string $reference
 * @property string|null $client_reference
 * @property string $sub_wallet_name
 * @property string $beneficiary_name
 * @property string $beneficiary_bank_name
 * @property string $beneficiary_account_last4
 * @property string $amount
 * @property string $currency
 * @property TransferStatus $status
 * @property string|null $provider_name
 * @property string|null $provider_reference
 * @property string|null $failure_reason
 * @property string|null $note
 * @property Carbon|null $requested_at
 * @property Carbon|null $processed_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $last_checked_at
 * @property Carbon|null $next_check_at
 * @property int $check_attempts
 * @property Carbon|null $reversed_at
 * @property Carbon|null $refunded_at
 * @property array<string,mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, TransferEvent> $events
 * @property-read TransferReversal|null $reversal
 */
class Transfer extends Model
{
    protected $table = 'transfers';

    protected $fillable = [
        'user_id',
        'parent_wallet_id',
        'sub_wallet_id',
        'beneficiary_id',
        'reference',
        'client_reference',
        'sub_wallet_name',
        'beneficiary_name',
        'beneficiary_bank_name',
        'beneficiary_account_last4',
        'amount',
        'currency',
        'status',
        'provider_name',
        'provider_reference',
        'failure_reason',
        'note',
        'requested_at',
        'processed_at',
        'completed_at',
        'last_checked_at',
        'next_check_at',
        'check_attempts',
        'reversed_at',
        'refunded_at',
        'metadata',
    ];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'parent_wallet_id' => 'integer',
        'sub_wallet_id' => 'integer',
        'beneficiary_id' => 'integer',
        'amount' => 'decimal:2',
        'status' => TransferStatus::class,
        'metadata' => 'array',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'next_check_at' => 'datetime',
        'check_attempts' => 'integer',
        'reversed_at' => 'datetime',
        'refunded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(TransferEvent::class, 'transfer_id', 'id')->latest('id');
    }

    public function reversal(): HasOne
    {
        return $this->hasOne(TransferReversal::class, 'transfer_id', 'id');
    }

    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
