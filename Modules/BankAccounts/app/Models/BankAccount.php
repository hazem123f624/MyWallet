<?php

namespace Modules\BankAccounts\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\BankAccounts\Enums\BankAccountStatus;

/**
 * @property int $id
 * @property int $user_id
 * @property int $bank_id
 * @property string $account_holder_name
 * @property string $account_number
 * @property string $account_number_hash
 * @property string $account_number_last4
 * @property string|null $iban
 * @property bool $is_primary
 * @property BankAccountStatus $status
 * @property Carbon|null $verified_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Bank $bank
 */
class BankAccount extends Model
{
    protected $table = 'bank_accounts';

    protected $fillable = [
        'user_id',
        'bank_id',
        'account_holder_name',
        'account_number',
        'account_number_hash',
        'account_number_last4',
        'iban',
        'is_primary',
        'status',
        'verified_at',
    ];

    protected $hidden = [
        'account_number',
        'account_number_hash',
        'iban',
    ];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'bank_id' => 'integer',
        'account_number' => 'encrypted',
        'iban' => 'encrypted',
        'is_primary' => 'boolean',
        'status' => BankAccountStatus::class,
        'verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'bank_id', 'id');
    }

    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('status', BankAccountStatus::Verified->value);
    }
}
