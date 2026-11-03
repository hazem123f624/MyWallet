<?php

namespace App\Services\MediaSystem;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * FileService — Unified file & image upload service.
 *
 * Supports multiple storage disks (local, public, s3), image resizing via
 * Intervention Image, quality compression, and batch uploads.
 *
 * ─── Quick Start ──────────────────────────────────────────────────────────────
 *
 *  $svc = app(FileService::class);
 *
 *  // 1) Upload a single file (uses default disk from .env FILESYSTEM_DISK)
 *  $path = $svc->upload($request->file('avatar'), folder: 'avatars');
 *
 *  // 2) Upload with a custom name
 *  $path = $svc->upload($request->file('avatar'), folder: 'avatars', name: 'profile_pic');
 *
 *  // 3) Upload multiple files at once
 *  $paths = $svc->uploadMany($request->file('documents'), folder: 'docs');
 *
 *  // 4) Upload multiple files with custom names
 *  $paths = $svc->uploadMany($request->file('docs'), folder: 'docs', names: ['cv', 'cover_letter']);
 *
 *  // 5) Upload image with resize variants (medium & small)
 *  $result = $svc->withResize([
 *      'medium' => [300, null],  // width 300, height auto (aspect ratio preserved)
 *      'small'  => [100, null],
 *  ])->upload($request->file('photo'), folder: 'profiles');
 *  // Returns: ['original' => 'uploads/profiles/photo.jpg', 'medium' => '...', 'small' => '...']
 *
 *  // 6) Upload with quality compression (default 100)
 *  $path = $svc->withQuality(75)->upload($request->file('photo'), folder: 'gallery');
 *
 *  // 7) Upload with resize + quality together
 *  $result = $svc->withResize(['thumb' => [150, 150]])
 *               ->withQuality(80)
 *               ->upload($request->file('photo'), folder: 'products');
 *
 *  // 8) Upload many images with resize
 *  $results = $svc->withResize(['small' => [100, null]])
 *                 ->uploadMany($request->file('photos'), folder: 'gallery');
 *
 *  // 9) Override disk for a specific call
 *  $path = $svc->useDisk('s3')->upload($file, folder: 'backups');
 *  $path = $svc->useDisk('public')->upload($file, folder: 'public_assets');
 *
 * // 10) Force a specific file extension
 *  $path = $svc->withExtension('webp')->upload($file, folder: 'images');
 *
 * // 11) Get public URL
 *  $url = $svc->url('uploads/avatars/profile_pic.jpg');
 *
 * // 12) Get temporary signed URL (private S3 buckets)
 *  $url = $svc->temporaryUrl('path/to/file.pdf', 60);
 *
 * // 13) Read file URL with fallback (replaces old FileTrait)
 *  $url  = $svc->readFile('avatar.jpg');
 *  $urls = $svc->readFile(['img1.jpg', 'img2.jpg'], array: true);
 *  $urls = $svc->readFile(['img1.jpg'], array: true, size: 'medium');
 *
 * // 14) Delete a file (also deletes resized variants)
 *  $svc->delete('uploads/profiles/photo.jpg');
 *
 * // 15) Delete multiple files
 *  $svc->deleteMany(['path1.jpg', 'path2.jpg']);
 *
 * // 16) Delete an entire folder
 *  $svc->deleteFolder('old_gallery');
 *
 * ─── Storage Paths ───────────────────────────────────────────────────────────
 *
 *  Uploads are stored under:  uploads/{folder}/file.jpg
 *
 * ─────────────────────────────────────────────────────────────────────────────
 */
class FileService
{
    /** The Laravel filesystem disk to use (local, public, s3, etc.) */
    private string $disk;

    /** Base path prefix for all uploaded files. */
    private string $basePath;

    /** MIME type map by extension. */
    private array $typeOfFiles;

    /** Resize variants: ['variant_name' => [width, height|null]] */
    private array $resize = [];

    /** Image encoding quality (1-100). */
    private int $quality = 100;

    /** Force a specific output extension (e.g. 'webp'). Null = keep original. */
    private ?string $forceExtension = null;

    /** File visibility ('public', 'private'). */
    private string $visibility = 'public';

    // ─────────────────────────────────────────────────────
    //  Constructor
    // ─────────────────────────────────────────────────────

    public function __construct()
    {
        $this->disk = 'public';
        $this->setBasePath();
        $this->initTypeOfFiles();
    }

    // ─────────────────────────────────────────────────────
    //  Fluent Setters (chainable, self-reset after upload)
    // ─────────────────────────────────────────────────────

    /**
     * Set the visibility for the next upload ('public', 'private').
     */
    public function withVisibility(string $visibility): static
    {
        $this->visibility = in_array($visibility, ['public', 'private']) ? $visibility : 'public';

        return $this;
    }

    /**
     * Switch the active disk at runtime.
     *
     * @param  string  $disk  e.g. 'local', 'public', 's3'
     */
    public function useDisk(string $disk): static
    {
        $this->disk = $disk;

        return $this;
    }

    /**
     * Set resize variants for the next upload.
     *
     * @param  array  $sizes  e.g. ['medium' => [300, null], 'small' => [100, null]]
     *                        [width, height] — null means auto (aspect ratio preserved)
     */
    public function withResize(array $sizes): static
    {
        $this->resize = $sizes;

        return $this;
    }

    /**
     * Set image encoding quality for the next upload (1-100).
     */
    public function withQuality(int $quality): static
    {
        $this->quality = max(1, min(100, $quality));

        return $this;
    }

    /**
     * Force a specific output extension.
     */
    public function withExtension(string $ext): static
    {
        $this->forceExtension = ltrim($ext, '.');

        return $this;
    }

    // ─────────────────────────────────────────────────────
    //  Upload — Single
    // ─────────────────────────────────────────────────────

    /**
     * Upload a single file.
     *
     * @param  UploadedFile  $file
     * @param  string        $folder  Sub-folder name (default 'global')
     * @param  string|null   $name    Custom name without extension (null = timestamp)
     * @return string|array  File path string, or array with 'original' + variant keys when resize is active
     */
    public function upload(UploadedFile $file, string $folder = 'global', ?string $name = null): string|array
    {
        $folderPath = $this->resolveFolder($folder);
        $name = $name ?? (string) time();

        $result = $this->processUpload($file, $folderPath, $name);

        $this->resetOptions();

        return $result;
    }

    // ─────────────────────────────────────────────────────
    //  Upload — Multiple
    // ─────────────────────────────────────────────────────

    /**
     * Upload multiple files at once.
     *
     * @param  array        $files   Array of UploadedFile instances
     * @param  string       $folder  Sub-folder name
     * @param  array        $names   Custom names (optional, same order as $files)
     * @return array        Array of paths (strings) or array of arrays when resize is active
     */
    public function uploadMany(array $files, string $folder = 'global', array $names = []): array
    {
        $folderPath = $this->resolveFolder($folder);
        $results = [];

        foreach ($files as $index => $file) {
            $name = ! empty($names) && isset($names[$index])
                ? $names[$index]
                : time() . '_' . ($index + 1);
            $results[] = $this->processUpload($file, $folderPath, $name);
        }

        $this->resetOptions();

        return $results;
    }

    // ─────────────────────────────────────────────────────
    //  Upload With Metadata (New)
    // ─────────────────────────────────────────────────────

    /**
     * Upload a single file and return full metadata.
     */
    public function uploadWithMeta(UploadedFile $file, string $folder = 'global', ?string $name = null): array
    {
        $folderPath = $this->resolveFolder($folder);
        $name = $name ?? (string) time();

        $uploadResult = $this->processUpload($file, $folderPath, $name);
        $meta = $this->getMetadata($file, $uploadResult);

        $this->resetOptions();

        return $meta;
    }

    /**
     * Upload multiple files and return full metadata for each.
     */
    public function uploadManyWithMeta(array $files, string $folder = 'global', array $names = []): array
    {
        $folderPath = $this->resolveFolder($folder);
        $results = [];

        foreach ($files as $index => $file) {
            $name = ! empty($names) && isset($names[$index])
                ? $names[$index]
                : time() . '_' . ($index + 1);

            $uploadResult = $this->processUpload($file, $folderPath, $name);
            $results[] = $this->getMetadata($file, $uploadResult);
        }

        $this->resetOptions();

        return $results;
    }

    // ─────────────────────────────────────────────────────
    //  Delete
    // ─────────────────────────────────────────────────────

    /**
     * Delete a single file from storage (including any resized variants).
     */
    public function delete(string $path): bool
    {
        if ($this->isPlaceholder($path)) {
            return true;
        }

        $this->deleteFromDisk($path);

        // Also delete resized variants if they exist
        $this->deleteResizedVariants($path);

        return true;
    }

    /**
     * Delete multiple files from storage.
     */
    public function deleteMany(array $paths): bool
    {
        foreach ($paths as $path) {
            $this->delete($path);
        }

        return true;
    }

    /**
     * Delete an entire sub-folder.
     */
    public function deleteFolder(string $folder): bool
    {
        $folderPath = $this->resolveFolder($folder);
        Storage::disk($this->disk)->deleteDirectory($folderPath);

        return true;
    }

    // ─────────────────────────────────────────────────────
    //  URL Reading
    // ─────────────────────────────────────────────────────

    /**
     * Get a public URL for a stored file.
     */
    public function url(string $path): string
    {
        $url = Storage::disk($this->disk)->url($path);

        // Ensure it's an absolute URL (includes protocol)
        if (! str_starts_with($url, 'http')) {
            $baseUrl = rtrim(config('app.url'), '/');
            return $baseUrl . '/' . ltrim($url, '/');
        }

        return $url;
    }

    /**
     * Get a temporary (pre-signed) URL — useful for private S3 buckets.
     * Falls back to normal url() if the disk does not support temporary URLs.
     */
    public function temporaryUrl(string $path, int $minutes = 60): string
    {
        try {
            return Storage::disk($this->disk)->temporaryUrl($path, now()->addMinutes($minutes));
        } catch (\RuntimeException $e) {
            return $this->url($path);
        }
    }

    /**
     * Read file URL(s) with fallback to 'notFound.png'.
     * Replaces the old FileTrait readFile() functionality.
     *
     * @param  string|array  $file   File name or array of file names
     * @param  bool          $array  Whether $file is an array
     * @param  string        $size   Size variant name (e.g. 'medium', 'small') — empty for original
     * @return string|array
     */
    public function readFile(string|array $file, bool $array = false, string $size = ''): string|array
    {
        if ($array && is_array($file)) {
            $paths = [];
            foreach ($file as $item) {
                $paths[] = $this->resolveFileUrl($item, $size);
            }

            return $paths;
        }

        return $this->resolveFileUrl(is_string($file) ? $file : '', $size);
    }

    // ─────────────────────────────────────────────────────
    //  Getters
    // ─────────────────────────────────────────────────────

    public function getDisk(): string
    {
        return $this->disk;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function getTypeOfFiles(): array
    {
        return $this->typeOfFiles;
    }

    /**
     * Get the MIME type for a given file extension.
     */
    public function getMimeType(string $extension): ?string
    {
        return $this->typeOfFiles[strtolower($extension)] ?? null;
    }

    /**
     * Check if a file extension is an image type.
     */
    public function isImage(string $extension): bool
    {
        $mime = $this->getMimeType(strtolower($extension));

        return $mime !== null && str_starts_with($mime, 'image/');
    }

    // ─────────────────────────────────────────────────────
    //  Private — Upload Processing
    // ─────────────────────────────────────────────────────

    /**
     * Process a single file upload with optional resize.
     */
    private function processUpload(UploadedFile $file, string $folderPath, string $name): string|array
    {
        $ext = $this->forceExtension ?? $file->getClientOriginalExtension();
        $fileName = $this->uniqueName($folderPath, $name, $ext);
        $fullName = "{$fileName}.{$ext}";
        $isImageFile = $this->isImage($ext);

        // Upload original file
        if ($isImageFile && ($this->quality < 100 || $this->forceExtension)) {
            // Use Intervention Image for quality/format conversion
            $image = \Image::make($file->getRealPath())->encode($ext, $this->quality);
            Storage::disk($this->disk)->put("{$folderPath}/{$fullName}", (string) $image, $this->visibility);
        } else {
            Storage::disk($this->disk)->putFileAs($folderPath, $file, $fullName, $this->visibility);
        }

        $originalPath = "{$folderPath}/{$fullName}";

        // Handle resize variants
        if (! empty($this->resize) && $isImageFile) {
            $variants = ['original' => $originalPath];

            foreach ($this->resize as $variantName => $dimensions) {
                [$width, $height] = $dimensions;
                $variantFolder = "{$folderPath}/{$variantName}";

                // Ensure variant folder exists on local disks
                $this->ensureDirectoryExists($variantFolder);

                $resizedImage = \Image::make($file->getRealPath())
                    ->resize($width, $height, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    })
                    ->encode($ext, $this->quality);

                Storage::disk($this->disk)->put("{$variantFolder}/{$fullName}", (string) $resizedImage, $this->visibility);

                $variants[$variantName] = "{$variantFolder}/{$fullName}";
            }

            return $variants;
        }

        return $originalPath;
    }

    // ─────────────────────────────────────────────────────
    //  Private — Path & Folder Helpers
    // ─────────────────────────────────────────────────────

    private function setBasePath(): void
    {
        $this->basePath = 'uploads';
    }

    private function resolveFolder(string $folder): string
    {
        $folder = $folder !== '' ? $folder : date('Y-m-d');

        return "{$this->basePath}/{$folder}";
    }

    /**
     * Generate a unique file name inside the given folder.
     */
    private function uniqueName(string $folderPath, string $name, string $ext, int $counter = 0): string
    {
        $candidate = $counter === 0 ? $name : "{$name}_{$counter}";
        $fullPath = "{$folderPath}/{$candidate}.{$ext}";

        if (Storage::disk($this->disk)->exists($fullPath)) {
            return $this->uniqueName($folderPath, $name, $ext, $counter + 1);
        }

        return $candidate;
    }

    private function ensureDirectoryExists(string $path): void
    {
        if (! Storage::disk($this->disk)->exists($path)) {
            Storage::disk($this->disk)->makeDirectory($path);
        }
    }

    // ─────────────────────────────────────────────────────
    //  Private — Delete Helpers
    // ─────────────────────────────────────────────────────

    private function deleteFromDisk(string $path): void
    {
        if (Storage::disk($this->disk)->exists($path)) {
            Storage::disk($this->disk)->delete($path);
        }
    }

    /**
     * Try to delete common resize variants (medium, small) for a file path.
     * Works by checking if variant directories exist in the same parent folder.
     */
    private function deleteResizedVariants(string $path): void
    {
        $directory = dirname($path);
        $fileName = basename($path);
        $commonVariants = ['medium', 'small', 'thumb', 'large'];

        foreach ($commonVariants as $variant) {
            $variantPath = "{$directory}/{$variant}/{$fileName}";
            $this->deleteFromDisk($variantPath);
        }
    }

    private function isPlaceholder(string $path): bool
    {
        return $path === 'notFound.png' || empty($path);
    }

    // ─────────────────────────────────────────────────────
    //  Private — URL Resolution
    // ─────────────────────────────────────────────────────

    /**
     * Resolve a file URL with optional size variant and fallback.
     *
     * Checks both the Storage disk AND the physical storage/app/ path
     * (for legacy files uploaded outside the Storage facade).
     */
    private function resolveFileUrl(string $file, string $size = ''): string
    {
        if ($this->isPlaceholder($file)) {
            return $this->fallbackUrl();
        }

        // If a size variant is requested, check if it exists
        if ($size !== '') {
            $directory = dirname($file);
            $fileName = basename($file);
            $variantPath = "{$directory}/{$size}/{$fileName}";

            if ($this->fileExists($variantPath)) {
                return $this->buildFileUrl($variantPath);
            }
        }

        // Check original
        if ($this->fileExists($file)) {
            return $this->buildFileUrl($file);
        }

        return $this->fallbackUrl();
    }

    /**
     * Check if file exists — tries Storage disk first, then physical storage/app/ path.
     */
    private function fileExists(string $path): bool
    {
        // 1. Check via Storage facade (for files uploaded through the new service)
        if (Storage::disk($this->disk)->exists($path)) {
            return true;
        }

        // 2. Check physical path at storage/app/ (for legacy files)
        return file_exists(storage_path("app/{$path}"));
    }

    /**
     * Build a URL for a file — handles both Storage-managed and legacy files.
     */
    private function buildFileUrl(string $path): string
    {
        // If the disk can resolve it, use that
        if (Storage::disk($this->disk)->exists($path)) {
            return $this->url($path);
        }

        // Legacy: build URL via the public/uploads symlink
        if ($this->disk === 's3') {
            return Storage::disk('s3')->url($path);
        }

        // path is like "uploads/2026-03-26/file.png" → served at /uploads/2026-03-26/file.png
        return rtrim(config('app.url', ''), '/') . '/' . ltrim($path, '/');
    }

    /**
     * Fallback URL when file is not found.
     */
    private function fallbackUrl(): string
    {
        return rtrim(config('app.url', ''), '/') . '/uploads/notFound.png';
    }

    // ─────────────────────────────────────────────────────
    //  Private — State Reset
    // ─────────────────────────────────────────────────────

    /**
     * Reset chainable options after each upload so they don't leak between calls.
     */
    private function resetOptions(): void
    {
        $this->resize = [];
        $this->quality = 100;
        $this->forceExtension = null;
        $this->visibility = 'public';
    }

    // ─────────────────────────────────────────────────────
    //  File Type Map
    // ─────────────────────────────────────────────────────

    private function initTypeOfFiles(): void
    {
        $this->typeOfFiles = [
            // Images
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'bmp'  => 'image/bmp',
            'webp' => 'image/webp',
            'svg'  => 'image/svg+xml',

            // Documents
            'pdf'  => 'application/pdf',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt'  => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt'  => 'text/plain',
            'csv'  => 'text/csv',

            // Code
            'php'  => 'application/x-httpd-php',
            'js'   => 'application/javascript',
            'css'  => 'text/css',
            'html' => 'text/html',
            'json' => 'application/json',
            'xml'  => 'application/xml',

            // Archives
            'zip'  => 'application/zip',
            'rar'  => 'application/x-rar-compressed',
            '7z'   => 'application/x-7z-compressed',
            'tar'  => 'application/x-tar',
            'gz'   => 'application/gzip',

            // Audio / Video
            'mp3'  => 'audio/mpeg',
            'wav'  => 'audio/wav',
            'mp4'  => 'video/mp4',
            'avi'  => 'video/x-msvideo',
            'mov'  => 'video/quicktime',
            'webm' => 'video/webm',
        ];
    }

    /**
     * Construct metadata array from upload result.
     */
    private function getMetadata(UploadedFile $file, string|array $uploadResult): array
    {
        $path = is_array($uploadResult) ? $uploadResult['original'] : $uploadResult;
        $ext = pathinfo($path, PATHINFO_EXTENSION);

        return [
            'path'          => $path,
            'extension'     => $ext,
            'mime_type'     => $this->getMimeType($ext) ?? $file->getMimeType(),
            'original_name' => $file->getClientOriginalName(),
            'size'          => $file->getSize(),
            'variants'      => is_array($uploadResult) ? $uploadResult : null,
        ];
    }
}
