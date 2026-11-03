<?php

namespace Modules\Admins\Models;

use App\Http\Traits\TranslateTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property int $model_id
 * @property string $lang
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Models $model
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelTranslate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelTranslate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelTranslate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelTranslate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelTranslate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelTranslate whereLang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelTranslate whereModelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelTranslate whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelTranslate whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ModelTranslate extends Model
{
    use TranslateTrait;

    protected $table = 'models_translate';

    public function model()
    {
        return $this->belongsTo(Models::class, 'model_id', 'id');
    }
}
