<?php

namespace Modules\Admins\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $admin_id
 * @property int $role_id
 * @property int $is_active
 * @property string|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, RolePermissions> $permissions
 * @property-read int|null $permissions_count
 * @property-read Roles $role
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminRoles newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminRoles newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminRoles query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminRoles whereAdminId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminRoles whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminRoles whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminRoles whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminRoles whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminRoles whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminRoles whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class AdminRoles extends Model
{
    protected $table = 'admin_roles';

    protected $fillable = [
        'role_id',
        'admin_id',
    ];

    protected $hidden = [];

    protected $casts = [];

    public $timestamps = true;

    public $attributes = [];

    public string $translateTableId = '';

    public array $translatedAttributes = [];

    public array $relatedModules = [];

    public function permissions()
    {
        return $this->hasMany(RolePermissions::class, 'role_id', 'role_id');
    }

    public function role()
    {
        return $this->belongsTo(Roles::class, 'role_id', 'id');
    }
}
