<?php

namespace Modules\Admins\Models;

use App\Http\Traits\TranslateTrait;
use App\Models\Otp;
use App\Services\ActivityLog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Admins\Database\Factories\AdminsFactory;
use Modules\Admins\DTOs\AdminsDTO;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $phone
 * @property string $password
 * @property string|null $avatar
 * @property string|null $last_login_at
 * @property int $is_active
 * @property string|null $change_password_token
 * @property Carbon|null $change_password_expired_at
 * @property string|null $remember_token
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Otp> $otps
 * @property-read int|null $otps_count
 * @property-read Collection<int, AdminRoles> $roles
 * @property-read int|null $roles_count
 * @property-read Collection<int, PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 *
 * @method static \Modules\Admins\Database\Factories\AdminsFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admins newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admins newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admins onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admins query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admins whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admins whereChangePasswordExpiredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admins whereChangePasswordToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admins whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admins whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admins whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admins whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admins whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admins whereLastLoginAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admins whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admins wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admins wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admins whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admins whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admins withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admins withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Admins extends Authenticatable
{
    use HasApiTokens, HasFactory;
    use LogsActivity;
    use SoftDeletes;
    use TranslateTrait;

    protected $table = 'admins';

    public $dto = AdminsDTO::class;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'avatar',
        'job_title',
        'contract_type',
        'is_active',
        'change_password_token',
        'change_password_expired_at',
        'last_login_at',
    ];

    protected $hidden = [];

    protected $casts = [
        'id' => 'integer',
        'password' => 'hashed',
        'otp_pin' => 'hashed',
        'is_active' => 'boolean',
        'change_password_expired_at' => 'datetime',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $timestamps = true;

    public $attributes = [];

    public string $foreignKey = 'admin_id';

    public array $translatedAttributes = [];

    public array $relatedModules = [
        'roles' => [
            'primary_keys' => ['admin_id', 'role_id'],
            'translate' => false, // ['attributes' => [] , 'id' => ''],
        ],
    ];

    public function roles(): HasMany
    {
        return $this->hasMany(AdminRoles::class, 'admin_id', 'id');
    }

    public function otps()
    {
        return $this->morphMany(Otp::class, 'otpable');
    }

    public function isSuperAdmin(): bool
    {
        $superRoleId = (int) data_get(config('admins.base_roles.super_role'), 'id', 2);

        return $this->roles()
            ->where('role_id', $superRoleId)
            ->whereHas('role', fn ($query) => $query->where('is_active', true))
            ->exists();
    }

    public function hasPermission($permission)
    {
        return $this->roles()
            ->whereHas('permissions', function ($q) use ($permission): void {
                $q->where('name', $permission);
            })
            ->exists();
    }

    protected static function newFactory()
    {
        return AdminsFactory::new();
    }
}
