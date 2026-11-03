<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $otpable_type
 * @property int $otpable_id
 * @property string $identifier
 * @property string $code
 * @property string $type
 * @property int $is_verified
 * @property int $attempts
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon $last_sent_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|\Eloquent $otpable
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp whereAttempts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp whereIdentifier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp whereIsVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp whereLastSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp whereOtpableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp whereOtpableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Otp extends Model
{
    protected $fillable = [
        'otpable_id',
        'otpable_type',
        'identifier',
        'code',
        'type',
        'is_verified',
        'attempts',
        'expires_at',
        'last_sent_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'code' => 'hashed',
    ];

    public function otpable()
    {
        return $this->morphTo();
    }
}
