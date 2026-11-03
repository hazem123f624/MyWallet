<?php

namespace App\Services\MediaSystem\Traits;

use App\Services\MediaSystem\Models\MediaFile;
use App\Services\MediaSystem\Models\MediaFolder;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasMediaFiles
{
    public function mediaFolders(): MorphMany
    {
        return $this->morphMany(MediaFolder::class, 'related', 'related_type', 'related_id');
    }

    public function mediaFiles(): HasManyThrough
    {
        return $this->hasManyThrough(
            MediaFile::class,
            MediaFolder::class,
            'related_id',
            'media_folder_id',
            'id',
            'id'
        )->where('media_folders.related_type', static::class);
    }
}
