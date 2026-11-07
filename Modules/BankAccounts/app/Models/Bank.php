<?php

namespace Modules\BankAccounts\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property string $name_en
 * @property string $name_ar
 * @property string|null $logo
 * @property bool $is_active
 * @property bool $supports_linking
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, BankAccount> $accounts
 */
class Bank extends Model
{
    protected $table = 'banks';

    protected $fillable = [
        'code',
        'name_en',
        'name_ar',
        'logo',
        'is_active',
        'supports_linking',
        'sort_order',
    ];

    protected $casts = [
        'id' => 'integer',
        'is_active' => 'boolean',
        'supports_linking' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(BankAccount::class, 'bank_id', 'id');
    }

    public function scopeSupported(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('supports_linking', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('sort_order')
            ->orderBy('name_en');
    }
}
