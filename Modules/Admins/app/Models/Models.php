<?php

namespace Modules\Admins\Models;

use App\Http\Traits\TranslateTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Modules\Admins\DTOs\ModelsDTO;

/**
 * @property int $id
 * @property string $name
 * @property int $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Permissions> $permissions
 * @property-read int|null $permissions_count
 * @property-read Collection<int, ModelTranslate> $translate
 * @property-read int|null $translate_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Models newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Models newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Models query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Models whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Models whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Models whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Models whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Models whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Models extends Model
{
    /**
     * @property string $name
     */
    use TranslateTrait;

    protected $table = 'models';

    protected $fillable = [
        'name',
        'is_active',
        'created_at',
    ];

    public $dto = ModelsDTO::class;

    protected $hidden = [];

    protected $casts = [];

    public $timestamps = true;

    protected $attributes = [];

    public array $translatedAttributes = ['title'];

    public array $relatedModules = [];

    public function permissions()
    {
        return $this->belongsToMany(Permissions::class, 'model_permissions', 'model_id', 'permission_id');
    }

    public function translate()
    {
        return $this->hasMany(ModelTranslate::class, 'model_id', 'id');
    }
}
