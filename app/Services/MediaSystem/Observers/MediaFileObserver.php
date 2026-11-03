<?php

namespace App\Services\MediaSystem\Observers;

use App\Services\MediaSystem\Models\MediaFile;
use App\Services\MediaSystem\Models\MediaFolder;

/**
 * Keeps media_folders.size in sync with its files. Any change to a file's size,
 * folder, or soft-delete state recomputes the affected folder rollup(s).
 */
class MediaFileObserver
{
    public function saved(MediaFile $file): void
    {
        $this->sync($file->media_folder_id);

        // File moved between folders — fix the folder it left too.
        if ($file->wasChanged('media_folder_id')) {
            $this->sync($file->getOriginal('media_folder_id'));
        }
    }

    public function deleted(MediaFile $file): void
    {
        $this->sync($file->media_folder_id);
    }

    public function restored(MediaFile $file): void
    {
        $this->sync($file->media_folder_id);
    }

    public function forceDeleted(MediaFile $file): void
    {
        $this->sync($file->media_folder_id);
    }

    private function sync($folderId): void
    {
        if (! $folderId) {
            return;
        }

        MediaFolder::query()->find($folderId)?->recalculateSize();
    }
}
