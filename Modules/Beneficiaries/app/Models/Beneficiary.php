<?php

namespace Modules\Beneficiaries\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $bank_name
 * @property string|null $bank_code
 * @property string $account_number
 * @property string $account_number_hash
 * @property string $account_number_last4
 * @property string|null $iban
 * @property bool $is_active
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Beneficiary extends Model
{
    use SoftDeletes;

    protected $table = 'beneficiaries';

    protected $fillable = [
        'user_id',
        'name',
        'bank_name',
        'bank_code',
        'account_number',
        'account_number_hash',
        'account_number_last4',
        'iban',
        'is_active',
    ];

    protected $hidden = [
        'account_number',
        'account_number_hash',
        'iban',
    ];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'account_number' => 'encrypted',
        'iban' => 'encrypted',
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
