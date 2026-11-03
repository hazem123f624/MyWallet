<?php

namespace Modules\Admins\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $role_id
 * @property int $model_id
 * @property int $permission_id
 * @property string $name
 * @property string|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RolePermissions newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RolePermissions newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RolePermissions query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RolePermissions whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RolePermissions whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RolePermissions whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RolePermissions whereModelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RolePermissions whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RolePermissions wherePermissionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RolePermissions whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RolePermissions whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class RolePermissions extends Model
{
    protected $table = 'role_permissions';

    protected $fillable = [
        'role_id',
        'model_id',
        'permission_id',
        'name',
    ];

    protected $hidden = [];

    protected $casts = [];

    public $timestamps = true;

    public $attributes = [];

    public string $translateTableId = '';

    public array $translatedAttributes = [];

    public array $relatedModules = [];

    public function translate()
    {
        return $this->hasMany(PermissionsTranslate::class, 'permission_id', 'permission_id');
    }

    public function model()
    {
        return $this->belongsTo(Models::class, 'model_id', 'id');
    }
}
