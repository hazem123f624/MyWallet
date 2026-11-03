<?php

namespace App\Services\Security\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $ip
 * @property string $event_type
 * @property array<array-key, mixed>|null $details
 * @property \Illuminate\Support\Carbon $created_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecurityLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecurityLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecurityLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecurityLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecurityLog whereDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecurityLog whereEventType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecurityLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecurityLog whereIp($value)
 * @mixin \Eloquent
 */
class SecurityLog extends Model
{
    protected $table = 'security_logs';

    public $timestamps = false;

    protected $fillable = [
        'ip',
        'event_type',
        'details',
        'risk_score',
        'fingerprint',
        'nonce',
        'flags',
        'user_agent',
        'request_method',
        'request_path',
        'api_client_id',
        'created_at',
    ];

    protected $casts = [
        'details' => 'array',
        'flags' => 'array',
        'risk_score' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * The API client that triggered this log entry.
     */
    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class, 'api_client_id');
    }
}
