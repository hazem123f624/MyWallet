<?php

namespace App\Services\MediaSystem;

use App\Services\MediaSystem\FileService;
use App\Services\MediaSystem\Models\MediaFile;
use App\Services\MediaSystem\Models\MediaFolder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * MediaService — Portable Media Management System.
 *
 * This service orchestrates file uploads (via FileService) and database persistence.
 * It supports standalone files, model-linked folders (polymorphic), and image resizing.
 *
 * ─── Usage Guide ─────────────────────────────────────────────────────────────
 *
 *  $media = app(MediaService::class);
 *
 *  // 1) Upload a standalone file
 *  $file = $media->upload($request->file('document'), storagePath: 'shared');
 *
 *  // 2) Upload and link to a Model (e.g., User, Product)
 *  $user = User::find(1);
 *  $file = $media->upload($request->file('avatar'), relatable: $user, folderName: 'Avatars');
 *
 *  // 3) Upload multiple files to a folder
 *  $files = $media->uploadMany($request->file('gallery'), relatable: $product, folderName: 'Showcase');
 *
 *  // 4) Image resizing with variants
 *  $file = $media->withResize(['thumb' => [150, 150]])
 *                ->upload($request->file('photo'));
 *  $thumbUrl = $file->variantUrl('thumb');
 *
 *  // 5) Get files for a model
 *  $folders = $media->getForRelatable($user);
 *  $allFiles = $media->getAllFilesForRelatable($user);
 *
 *  // 6) Get URL or Temporary URL
 *  $url = $media->url($mediaFile);
 *  $tempUrl = $media->temporaryUrl($mediaFile, 30); // 30 minutes
 *
 *  // 7) Deleting Media
 *  $media->delete($mediaFile); // Deletes from DB and Physical Storage
 *
 * ─────────────────────────────────────────────────────────────────────────────
 */
class MediaService
{
    protected FileService $fileService;

    /** Current upload disk. */
    protected ?string $disk = null;

    /** Current upload visibility ('public', 'private'). */
    protected string $visibility = 'public';

    /** Current upload tags. */
    protected array $tags = [];

    /** Current upload/retrieval type ('media', 'attachment'). */
    protected string $type = 'media';

    /** Current upload category ('general', 'avatar', etc.). */
    protected string $category = 'general';

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    // ─────────────────────────────────────────────────────
    //  Upload Operations
    // ─────────────────────────────────────────────────────

    /**
     * Upload a single file and store it in the database.
     */
    public function upload(
        UploadedFile $file,
        ?Model $relatable = null,
        string $folderName = 'default',
        ?string $storagePath = null,
        ?string $name = null,
        ?string $type = null
    ): MediaFile {
        $storagePath = $storagePath ?? $folderName;
        $type = $type ?? $this->type;
        return DB::transaction(function () use ($file, $relatable, $folderName, $storagePath, $name, $type) {
            try {
                // Apply fluent options
                $this->fileService->withVisibility($this->visibility);
                if ($this->disk) {
                    $this->fileService->useDisk($this->disk);
                }

                // Use the enhanced FileService to get full metadata
                $meta = $this->fileService->uploadWithMeta($file, $storagePath, $name);

                $folderId = $this->resolveFolderId($relatable, $folderName, $type);

                $mediaFile = MediaFile::create([
                    'media_folder_id' => $folderId,
                    'disk'            => $this->disk ?? config('filesystems.default'),
                    'visibility'      => $this->visibility,
                    'path'            => $meta['path'],
                    'extension'       => $meta['extension'],
                    'mime_type'       => $meta['mime_type'],
                    'original_name'   => $meta['original_name'],
                    'size'            => $meta['size'],
                    'variants'       => $meta['variants'],
                    'tags'            => $this->tags,
                    'version'         => 1,
                    'is_current'      => true,
                ]);

                return $mediaFile;
            } finally {
                $this->resetSystemOptions();
            }
        });
    }

    /**
     * Upload multiple files and store them in the database.
     */
    public function uploadMany(
        array $files,
        ?Model $relatable = null,
        string $folderName = 'default',
        ?string $storagePath = null,
        array $names = [],
        ?string $type = null
    ): Collection {
        $storagePath = $storagePath ?? $folderName;
        $type = $type ?? $this->type;
        return DB::transaction(function () use ($files, $relatable, $folderName, $storagePath, $names, $type) {
            try {
                $folderId = $this->resolveFolderId($relatable, $folderName, $type);

                // Apply fluent options
                $this->fileService->withVisibility($this->visibility);
                if ($this->disk) {
                    $this->fileService->useDisk($this->disk);
                }

                $metas = $this->fileService->uploadManyWithMeta($files, $storagePath, $names);

                $records = new Collection();
                foreach ($metas as $meta) {
                    $records->push(MediaFile::create([
                        'media_folder_id' => $folderId,
                        'disk'            => $this->disk ?? config('filesystems.default'),
                        'visibility'      => $this->visibility,
                        'path'            => $meta['path'],
                        'extension'       => $meta['extension'],
                        'mime_type'       => $meta['mime_type'],
                        'original_name'   => $meta['original_name'],
                        'size'            => $meta['size'],
                        'variants'       => $meta['variants'],
                        'tags'            => $this->tags,
                        'version'         => 1,
                        'is_current'      => true,
                    ]));
                }

                return $records;
            } finally {
                $this->resetSystemOptions();
            }
        });
    }

    /**
     * Update an existing MediaFile with a new version.
     * Keeps the old record in DB (soft deleted or as a version) and creates a new one.
     */
    public function updateFile(MediaFile $oldFile, UploadedFile $newFile): MediaFile
    {
        return DB::transaction(function () use ($oldFile, $newFile) {
            // 1. Upload new version
            $newVersionNumber = $oldFile->version + 1;
            $storagePath = dirname($oldFile->path);

            // Ensure options are consistent
            $this->withVisibility($oldFile->visibility);
            if ($oldFile->disk) {
                $this->useDisk($oldFile->disk);
            }

            $newMedia = $this->upload(
                $newFile,
                relatable: $oldFile->folder?->relatable,
                folderName: $oldFile->folder?->name,
                storagePath: $storagePath
            );

            // 2. Link versions
            $newMedia->update([
                'parent_id'       => $oldFile->id,
                'version'         => $newVersionNumber,
                'media_folder_id' => $oldFile->media_folder_id,
                'tags'            => array_unique(array_merge($oldFile->tags ?? [], $this->tags)),
                'is_current'      => true,
            ]);

            // 3. Mark old file as not current
            $oldFile->update(['is_current' => false]);

            return $newMedia;
        });
    }

    // ─────────────────────────────────────────────────────
    //  Standalone File Operations (Storage Only)
    // ─────────────────────────────────────────────────────

    /**
     * Upload a file to storage ONLY (no Database record).
     * Returns the path string or resize array.
     */
    public function uploadToStorage(UploadedFile $file, string $folder = 'global', ?string $name = null): string|array
    {
        $this->fileService->withVisibility($this->visibility);
        if ($this->disk) {
            $this->fileService->useDisk($this->disk);
        }

        $result = $this->fileService->upload($file, $folder, $name);

        $this->resetSystemOptions();

        return $result;
    }

    /**
     * Upload multiple files to storage ONLY (no Database record).
     */
    public function uploadManyToStorage(array $files, string $folder = 'global', array $names = []): array
    {
        $this->fileService->withVisibility($this->visibility);
        if ($this->disk) {
            $this->fileService->useDisk($this->disk);
        }

        $results = $this->fileService->uploadMany($files, $folder, $names);

        $this->resetSystemOptions();

        return $results;
    }

    // ─────────────────────────────────────────────────────
    //  Retrieval & URL Operations
    // ─────────────────────────────────────────────────────

    /**
     * Get the public URL for a MediaFile, path, or legacy file name.
     */
    public function url(MediaFile|string $file): string
    {
        if ($file instanceof MediaFile) {
            return $file->url; // Uses specific logic in Model (visibility/disk)
        }

        return $this->fileService->url($file);
    }

    /**
     * Get a temporary (signed) URL — useful for private S3 buckets.
     */
    public function temporaryUrl(MediaFile|string $file, int $minutes = 60): string
    {
        $path = $file instanceof MediaFile ? $file->path : $file;
        $disk = $file instanceof MediaFile ? $file->disk : $this->disk;

        if ($disk) {
            return $this->fileService->useDisk($disk)->temporaryUrl($path, $minutes);
        }

        return $this->fileService->temporaryUrl($path, $minutes);
    }

    /**
     * Read file URL(s) with fallback to 'notFound.png'.
     */
    public function readFile(string|array $file, bool $array = false, string $size = ''): string|array
    {
        return $this->fileService->readFile($file, $array, $size);
    }

    /**
     * Find a MediaFile by ID.
     */
    public function find(int $id, bool $withTrashed = false): ?MediaFile
    {
        $query = MediaFile::query();
        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->find($id);
    }

    /**
     * Find a MediaFile by its storage path.
     */
    public function findByPath(string $path, bool $withTrashed = false): ?MediaFile
    {
        $query = MediaFile::where('path', $path);
        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->first();
    }

    /**
     * Get all media folders for a relatable model.
     */
    public function getForRelatable(Model $relatable, string $type = 'media'): Collection
    {
        return MediaFolder::where('related_id', $relatable->getKey())
            ->where('related_type', get_class($relatable))
            ->where('type', $type)
            ->with('files')
            ->get();
    }

    /**
     * Get paginated media folders for a relatable model with filters.
     */
    public function getPaginatedForRelatable(Model $relatable, string $type = 'media', array $filters = [], int $perPage = 15)
    {
        $query = MediaFolder::where('related_id', $relatable->getKey())
            ->where('related_type', get_class($relatable))
            ->where('type', $type)
            ->with('files');

        // Filter by category
        if (!empty($filters['category'])) {
            $query->whereIn('category', (array) $filters['category']);
        }

        // Filter by date range
        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get a specific folder for a relatable model with its files.
     */
    public function getFolder(Model $relatable, string $folderName, string $type = 'media'): ?MediaFolder
    {
        return MediaFolder::where('related_id', $relatable->getKey())
            ->where('related_type', get_class($relatable))
            ->where('name', $folderName)
            ->where('type', $type)
            ->with('files')
            ->first();
    }

    /**
     * Get ALL files for a relatable model across its folders.
     */
    public function getAllFilesForRelatable(Model $relatable): Collection
    {
        return MediaFile::whereHas('folder', function ($query) use ($relatable) {
            $query->where('related_id', $relatable->getKey())
                  ->where('related_type', get_class($relatable));
        })->get();
    }

    // ─────────────────────────────────────────────────────
    //  Delete Operations
    // ─────────────────────────────────────────────────────

    /**
     * Delete a MediaFile.
     *
     * @param  MediaFile|int|string  $file
     * @param  bool                  $force  True = permanent delete (disk + DB), False = soft delete (DB only)
     * @return bool
     */
    public function delete(MediaFile|int|string $file, bool $force = false): bool
    {
        $record = null;
        $path = null;

        if ($file instanceof MediaFile) {
            $record = $file;
        } elseif (is_numeric($file)) {
            $record = MediaFile::withTrashed()->find($file);
        } else {
            $record = MediaFile::withTrashed()->where('path', $file)->first();
        }

        if (! $record) {
            // If no record, but a path was provided, try physical delete
            if (is_string($file) && ! is_numeric($file)) {
                return $this->fileService->delete($file);
            }

            return false;
        }

        if ($force) {
            // 1. Delete physical files (including variants)
            $this->fileService->useDisk($record->disk ?? 'local')->delete($record->path);

            // 2. Delete DB record permanently
            return (bool) $record->forceDelete();
        }

        // Soft delete record
        return (bool) $record->delete();
    }

    /**
     * Delete multiple MediaFiles.
     */
    public function deleteMany(array $files, bool $force = false): bool
    {
        foreach ($files as $file) {
            $this->delete($file, $force);
        }

        return true;
    }

    /**
     * Restore a soft-deleted MediaFile.
     */
    public function restore(int|string $file): bool
    {
        $record = is_numeric($file)
            ? MediaFile::onlyTrashed()->find($file)
            : MediaFile::onlyTrashed()->where('path', $file)->first();

        return $record ? (bool) $record->restore() : false;
    }

    /**
     * Delete an entire physical directory from storage.
     */
    public function deleteFolder(string $folder, bool $deleteDBRecords = false): bool
    {
        if ($deleteDBRecords) {
            // This is a slow operation, use with caution
            MediaFile::where('path', 'like', $folder . '/%')->delete();
        }

        return $this->fileService->deleteFolder($folder);
    }

    /**
     * Delete a MediaFolder and all its files.
     */
    public function deleteMediaFolder(MediaFolder $folder, bool $force = false): void
    {
        // Delete all files in the folder first
        foreach ($folder->files as $file) {
            $this->deleteFile($file, $force);
        }

        if ($force) {
            $folder->forceDelete();
        } else {
            $folder->delete();
        }
    }

    /**
     * Delete a single media file and its physical storage.
     */
    public function deleteFile(MediaFile $file, bool $force = false): void
    {
        // 1. Delete physical file
        $disk = $file->disk ?? config('filesystems.default');
        if (Storage::disk($disk)->exists($file->path)) {
            Storage::disk($disk)->delete($file->path);
        }

        // 2. Delete variants
        if ($file->variants) {
            foreach ($file->variants as $variantPath) {
                if (Storage::disk($disk)->exists($variantPath)) {
                    Storage::disk($disk)->delete($variantPath);
                }
            }
        }

        // 3. Delete database record
        if ($force) {
            $file->forceDelete();
        } else {
            $file->delete();
        }
    }

    // ─────────────────────────────────────────────────────
    //  Helper Operations
    // ─────────────────────────────────────────────────────

    /**
     * Check if a file extension represents an image.
     */
    public function isImage(string $extension): bool
    {
        return $this->fileService->isImage($extension);
    }

    /**
     * Get the MIME type for a given file extension.
     */
    public function getMimeType(string $extension): ?string
    {
        return $this->fileService->getMimeType($extension);
    }

    /**
     * Get the entire MIME type map.
     */
    public function getTypeOfFiles(): array
    {
        return $this->fileService->getTypeOfFiles();
    }

    // ─────────────────────────────────────────────────────
    //  Fluent Setters (Proxies to FileService)
    // ─────────────────────────────────────────────────────

    /**
     * Set the visibility for the next upload ('public', 'private').
     */
    public function withVisibility(string $visibility): static
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Set the tags for the next upload.
     */
    public function withTags(array $tags): static
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Set the type for the next upload ('media', 'attachment').
     */
    public function withType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function withResize(array $sizes): static
    {
        $this->fileService->withResize($sizes);

        return $this;
    }

    public function withQuality(int $quality): static
    {
        $this->fileService->withQuality($quality);

        return $this;
    }

    public function withExtension(string $ext): static
    {
        $this->fileService->withExtension($ext);

        return $this;
    }

    /**
     * Set the category for the next upload.
     */
    public function withCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function useDisk(string $disk): static
    {
        $this->disk = $disk;

        return $this;
    }

    // ─────────────────────────────────────────────────────
    //  Private Helpers
    // ─────────────────────────────────────────────────────

    private function resolveFolderId(?Model $relatable, string $folderName, string $type = 'media'): ?int
    {
        if (! $relatable) {
            return null;
        }

        $folder = MediaFolder::firstOrCreate([
            'name'         => $folderName,
            'related_id'   => $relatable->getKey(),
            'related_type' => get_class($relatable),
            'type'         => $type,
            'category'     => $this->category,
        ]);

        return $folder->id;
    }

    /**
     * Reset local options after upload.
     */
    private function resetSystemOptions(): void
    {
        $this->disk = null;
        $this->visibility = 'public';
        $this->tags = [];
        $this->type = 'media';
        $this->category = 'general';
    }
}
