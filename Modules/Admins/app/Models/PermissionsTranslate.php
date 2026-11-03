<?php

namespace Modules\Admins\Models;

use App\Http\Traits\TranslateTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $permission_id
 * @property string $title
 * @property string $lang
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Permissions $permissions
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionsTranslate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionsTranslate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionsTranslate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionsTranslate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionsTranslate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionsTranslate whereLang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionsTranslate wherePermissionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionsTranslate whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionsTranslate whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PermissionsTranslate extends Model
{
    use TranslateTrait;

    protected $table = 'permissions_translate';

    public function permissions()
    {
        return $this->belongsTo(Permissions::class, 'permission_id', 'id');
    }
}
