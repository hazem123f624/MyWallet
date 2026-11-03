<?php

namespace App\Services\Security\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $api_key
 * @property string $secret
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $last_rotated_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiClient newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiClient newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiClient query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiClient whereApiKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiClient whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiClient whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiClient whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiClient whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiClient whereLastRotatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiClient whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiClient whereSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiClient whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ApiClient extends Model
{
    protected $table = 'api_clients';

    protected $fillable = [
        'name',
        'api_key',
        'secret',
        'is_active',
        'expires_at',
        'last_rotated_at',
    ];

    protected $hidden = [
        'secret',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'last_rotated_at' => 'datetime',
    ];

    /**
     * Check if this client's API key has expired.
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false; // No expiry = lives forever
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check if this client is fully valid (active + not expired).
     */
    public function isValid(): bool
    {
        return $this->is_active && ! $this->isExpired();
    }
}
