<?php

namespace Modules\Admins\Models;

use App\Http\Traits\TranslateTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $role_id
 * @property string $name
 * @property string $lang
 * @property string|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Roles|null $model
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RoleTranslate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RoleTranslate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RoleTranslate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RoleTranslate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RoleTranslate whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RoleTranslate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RoleTranslate whereLang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RoleTranslate whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RoleTranslate whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RoleTranslate whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class RoleTranslate extends Model
{
    use TranslateTrait;

    protected $table = 'roles_translate';

    public function model()
    {
        return $this->belongsTo(Roles::class, 'role_id', 'id');
    }
}
