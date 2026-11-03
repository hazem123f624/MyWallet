<?php

namespace Modules\Admins\Models;

use App\Http\Traits\TranslateTrait;
use App\Services\ActivityLog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Modules\Admins\DTOs\RolesDTO;

/**
 * @property int $id
 * @property int $is_active
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, RolePermissions> $permissions
 * @property-read int|null $permissions_count
 * @property-read Collection<int, RoleTranslate> $translate
 * @property-read int|null $translate_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Roles newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Roles newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Roles onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Roles query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Roles whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Roles whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Roles whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Roles whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Roles whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Roles withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Roles withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Roles extends Model
{
    use LogsActivity;
    use SoftDeletes;
    use TranslateTrait;

    protected $table = 'roles';

    public $dto = RolesDTO::class;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [];

    protected $casts = [];

    public $timestamps = true;

    public $attributes = [];

    public string $foreignKey = 'role_id';

    // foreignKey
    public array $translatedAttributes = ['name', 'description'];

    public array $relatedModules = [
        'permissions' => [
            'primary_keys' => ['role_id', 'model_id', 'permission_id'],
            'translate' => false, // ['attributes' => [] , 'id' => ''],
        ],
    ];

    public function permissions()
    {
        return $this->hasMany(RolePermissions::class, 'role_id', 'id');
    }

    public function translate()
    {
        return $this->hasMany(RoleTranslate::class, 'role_id', 'id');
    }

    public function admins()
    {
        return $this->hasMany(AdminRoles::class, 'role_id', 'id');
    }

    public function adminsList()
    {
        return $this->belongsToMany(Admins::class, 'admin_roles', 'role_id', 'admin_id');
    }
}
