<?php

namespace Modules\Users\Models;

use App\Http\Traits\TranslateTrait;
use App\Models\Otp;
use App\Services\ActivityLog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Modules\Users\Database\Factories\UserFactory;
use Modules\Users\DTOs\UsersDTO;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string $password
 * @property string|null $transaction_pin
 * @property int $transaction_pin_failed_attempts
 * @property Carbon|null $transaction_pin_locked_until
 * @property string|null $avatar
 * @property int|null $country_id
 * @property string|null $postal_code
 * @property string|null $main_address
 * @property bool $is_active
 * @property Carbon|null $phone_verified_at
 * @property string|null $change_password_token
 * @property Carbon|null $change_password_expired_at
 * @property Carbon|null $last_login_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class User extends Authenticatable
{
    public $dto = UsersDTO::class;

    use HasApiTokens, HasFactory;
    use LogsActivity;
    use SoftDeletes;
    use TranslateTrait;

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'country_id',
        'phone',
        'password',
        'transaction_pin',
        'transaction_pin_failed_attempts',
        'transaction_pin_locked_until',
        'avatar',
        'postal_code',
        'main_address',
        'is_active',
        'last_login_at',
        'phone_verified_at',
        'change_password_token',
        'change_password_expired_at',
        'failed_login_attempts',
        'locked_until',
        'lock_level',
    ];

    protected $hidden = [
        'password',
        'transaction_pin',
        'remember_token',
    ];

    protected $casts = [
        'id' => 'integer',
        'password' => 'hashed',
        'transaction_pin' => 'hashed',
        'transaction_pin_failed_attempts' => 'integer',
        'transaction_pin_locked_until' => 'datetime',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'change_password_expired_at' => 'datetime',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'locked_until' => 'datetime',
    ];

    public function otps()
    {
        return $this->morphMany(Otp::class, 'otpable');
    }

    protected static function newFactory()
    {
        return UserFactory::new();
    }
}
