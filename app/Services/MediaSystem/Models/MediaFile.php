<?php

namespace App\Services\MediaSystem\Models;

use App\Services\MediaSystem\FileService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $media_folder_id
 * @property string|null $disk
 * @property string $visibility
 * @property string $path
 * @property string $extension
 * @property string $mime_type
 * @property string|null $original_name
 * @property int|null $size
 * @property array<array-key, mixed>|null $variants
 * @property array<array-key, mixed>|null $tags
 * @property int|null $parent_id
 * @property int $version
 * @property bool $is_current
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Services\MediaSystem\Models\MediaFolder|null $folder
 * @property-read string $url
 * @property-read MediaFile|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MediaFile> $versions
 * @property-read int|null $versions_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFile latestVersion()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFile onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFile whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFile whereDisk($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFile whereExtension($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFile whereIsCurrent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFile whereMediaFolderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFile whereMimeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFile whereOriginalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFile whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFile wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFile whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFile whereTags($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFile whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFile whereVariants($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFile whereVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFile whereVisibility($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFile withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaFile withoutTrashed()
 * @mixin \Eloquent
 */
class MediaFile extends Model
{
    use SoftDeletes;

    protected $table = 'media_files';

    protected $fillable = [
        'media_folder_id',
        'disk',
        'visibility',
        'path',
        'extension',
        'mime_type',
        'original_name',
        'size',
        'variants',
        'tags',
        'parent_id',
        'version',
        'is_current',
    ];

    protected $casts = [
        'variants'   => 'array',
        'tags'       => 'array',
        'size'       => 'integer',
        'version'    => 'integer',
        'is_current' => 'boolean',
        'media_folder_id' => 'integer',
        'parent_id'  => 'integer',
    ];

    /**
     * Get the folder this file belongs to.
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'media_folder_id');
    }

    /**
     * Versioning: Get all versions of this file.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(MediaFile::class, 'parent_id');
    }

    /**
     * Versioning: Get the parent file (if this is a version).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'parent_id');
    }

    /**
     * Versioning: Scope to only latest (current) versions.
     */
    public function scopeLatestVersion($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Get the full URL of the file.
     */
    public function getUrlAttribute(): string
    {
        if ($this->visibility === 'private' && $this->disk === 's3') {
            return app(FileService::class)->useDisk('s3')->temporaryUrl($this->path);
        }

        return app(FileService::class)->useDisk($this->disk ?? config('filesystems.default'))->url($this->path);
    }

    /**
     * Get the URL of a specific variant.
     */
    public function variantUrl(string $variant): ?string
    {
        if (isset($this->variants[$variant])) {
            $path = $this->variants[$variant];
            if ($this->visibility === 'private' && $this->disk === 's3') {
                return app(FileService::class)->useDisk('s3')->temporaryUrl($path);
            }

            return app(FileService::class)->useDisk($this->disk ?? config('filesystems.default'))->url($path);
        }

        return null;
    }
}
