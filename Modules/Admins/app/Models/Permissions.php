<?php

namespace Modules\Admins\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property int $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, PermissionsTranslate> $translate
 * @property-read int|null $translate_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permissions newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permissions newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permissions query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permissions whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permissions whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permissions whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permissions whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permissions whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Permissions extends Model
{
    /**
     * @property string $name
     */
    protected $table = 'permissions';

    public function translate()
    {
        return $this->hasMany(PermissionsTranslate::class, 'permission_id', 'id');
    }
}
