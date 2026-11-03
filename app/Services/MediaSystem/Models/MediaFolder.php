<?php

namespace App\Services\MediaSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $name
 * @property string $category
 * @property string $type
 * @property int|null $related_id
 * @property string|null $related_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Services\MediaSystem\Models\MediaFile> $files
 * @property-read int|null $files_count
 * @property-read Model|\Eloquent|null $relatable
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFolder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFolder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFolder onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFolder query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFolder whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFolder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFolder whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFolder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFolder whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFolder whereRelatedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFolder whereRelatedType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFolder whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFolder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFolder withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFolder withoutTrashed()
 * @mixin \Eloquent
 */
class MediaFolder extends Model
{
    use SoftDeletes;
    protected $table = 'media_folders';

    protected $fillable = [
        'name',
        'category',
        'type',
        'size',
        'related_id',
        'related_type',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function files(): HasMany
    {
        return $this->hasMany(MediaFile::class, 'media_folder_id');
    }

    public function relatable(): MorphTo
    {
        return $this->morphTo('related', 'related_type', 'related_id');
    }

    /**
     * Recompute `size` from the folder's current (non-trashed) files and persist
     * it quietly (no model events, to avoid feedback loops).
     */
    public function recalculateSize(): void
    {
        $sum = (int) $this->files()->sum('size');

        if ((int) $this->size !== $sum) {
            $this->forceFill(['size' => $sum])->saveQuietly();
        }
    }
}
