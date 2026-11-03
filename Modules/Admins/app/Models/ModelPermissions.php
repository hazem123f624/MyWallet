<?php

namespace Modules\Admins\Models;

use App\Http\Traits\TranslateTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $model_id
 * @property int $permission_id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelPermissions newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelPermissions newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelPermissions query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelPermissions whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelPermissions whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelPermissions whereModelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelPermissions whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelPermissions wherePermissionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelPermissions whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ModelPermissions extends Model
{
    use TranslateTrait;

    protected $table = 'model_permissions';
}
