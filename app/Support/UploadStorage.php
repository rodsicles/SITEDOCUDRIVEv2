<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Central storage for user uploads (documents, exams, guides, tasks, certificates).
 *
 * Local dev: disk "local" → storage/app/private (persistent on your machine).
 * Laravel Cloud: set FILESYSTEM_UPLOAD_DISK to your Object Storage bucket disk name
 * (see Laravel Cloud → Environment → Object Storage). Files survive deploys/restarts.
 */
class UploadStorage
{
    private const UPLOAD_OPTIONS = [];

    public static function diskName(): string
    {
        return (string) config('filesystems.upload_disk', 'local');
    }

    public static function disk(): Filesystem
    {
        static::assertDiskConfigured();

        return Storage::disk(static::diskName());
    }

    public static function isLocal(): bool
    {
        return static::diskName() === 'local';
    }

    /**
     * Fail fast when cloud upload disk is selected but credentials/bucket are missing.
     */
    public static function assertDiskConfigured(): void
    {
        if (static::isLocal()) {
            return;
        }

        $diskName = static::diskName();
        $disk = config("filesystems.disks.{$diskName}");

        if (!is_array($disk)) {
            throw new UploadStorageException(
                "Upload disk \"{$diskName}\" is not defined. Set FILESYSTEM_UPLOAD_DISK in Laravel Cloud to your Object Storage disk name (usually s3)."
            );
        }

        if (($disk['driver'] ?? '') === 's3') {
            foreach (['key', 'secret', 'bucket'] as $key) {
                if (empty($disk[$key])) {
                    throw new UploadStorageException(
                        'Object storage is not configured. Attach Object Storage in Laravel Cloud and ensure AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, and AWS_BUCKET are set.'
                    );
                }
            }
        }
    }

    public static function assertSafeRelativePath(string $path): void
    {
        if ($path === '' || str_contains($path, '..') || str_contains($path, './') || str_starts_with($path, '/')) {
            abort(403, 'Invalid file path');
        }
    }

    /**
     * Normalize a stored object key for comparisons (S3/R2 and local).
     */
    public static function normalizeStoragePath(string $path): string
    {
        return str_replace('\\', '/', ltrim($path, '/'));
    }

    /**
     * Resolve the top-level upload directory for a stored relative path.
     */
    public static function uploadDirectoryForPath(string $path): string
    {
        $path = static::normalizeStoragePath($path);
        static::assertSafeRelativePath($path);

        foreach (['teaching-guides', 'exam-questionnaires', 'task-attachments', 'documents'] as $directory) {
            if (str_starts_with($path, $directory . '/')) {
                return $directory;
            }
        }

        return 'documents';
    }

    public static function assertPathInDirectory(string $path, string $directory): void
    {
        $path = static::normalizeStoragePath($path);
        static::assertSafeRelativePath($path);
        $prefix = rtrim($directory, '/') . '/';
        if (!str_starts_with($path, $prefix)) {
            abort(403, 'Unauthorized file access');
        }
    }

    public static function assertPathAllowed(string $path): void
    {
        static::assertResolvedPath($path, static::uploadDirectoryForPath($path));
    }

    /**
     * Path traversal check: realpath on local disk, prefix check on cloud disks.
     */
    public static function assertResolvedPath(string $path, string $directory): void
    {
        static::assertPathInDirectory($path, $directory);

        if (!static::isLocal()) {
            return;
        }

        $allowedDir = static::disk()->path($directory);
        $fullPath = static::disk()->path($path);
        $realAllowed = realpath($allowedDir);
        $realFile = realpath($fullPath);

        if ($realAllowed && $realFile && !str_starts_with($realFile, $realAllowed)) {
            abort(403, 'Unauthorized file access');
        }
    }

    public static function exists(string $path): bool
    {
        return static::disk()->exists($path);
    }

    public static function mimeType(string $path): ?string
    {
        return static::disk()->mimeType($path) ?: null;
    }

    public static function delete(string $path): bool
    {
        return static::disk()->delete($path);
    }

    public static function size(string $path): int
    {
        return (int) static::disk()->size($path);
    }

    public static function putFileAs(string $directory, UploadedFile $file, string $name): string
    {
        $relativePath = rtrim($directory, '/') . '/' . $name;

        try {
            $stored = static::disk()->putFileAs($directory, $file, $name, static::UPLOAD_OPTIONS);
        } catch (\Throwable $e) {
            static::fail('Could not save the uploaded file to storage.', $e);
        }

        if ($stored === false || !static::exists($relativePath)) {
            static::fail('The file was not saved to storage. Check bucket permissions and FILESYSTEM_UPLOAD_DISK on Laravel Cloud.');
        }

        return $relativePath;
    }

    public static function store(UploadedFile $file, string $directory): string
    {
        try {
            $path = $file->store($directory, [
                'disk' => static::diskName(),
            ]);
        } catch (\Throwable $e) {
            static::fail('Could not save the uploaded file to storage.', $e);
        }

        if ($path === false || !static::exists($path)) {
            static::fail('The file was not saved to storage. Check bucket permissions and FILESYSTEM_UPLOAD_DISK on Laravel Cloud.');
        }

        return $path;
    }

    public static function storeAs(UploadedFile $file, string $directory, string $name): string
    {
        $relativePath = rtrim($directory, '/') . '/' . $name;

        try {
            $stored = $file->storeAs($directory, $name, [
                'disk' => static::diskName(),
            ]);
        } catch (\Throwable $e) {
            static::fail('Could not save the uploaded file to storage.', $e);
        }

        if ($stored === false || !static::exists($relativePath)) {
            static::fail('The file was not saved to storage. Check bucket permissions and FILESYSTEM_UPLOAD_DISK on Laravel Cloud.');
        }

        return $relativePath;
    }

    /**
     * Write a file that was built on local temp disk (Word/PDF generation) into upload storage.
     */
    public static function putFromLocalFile(string $relativePath, string $absoluteLocalPath): void
    {
        static::assertSafeRelativePath($relativePath);

        if (!is_readable($absoluteLocalPath)) {
            throw new \RuntimeException('Cannot read local file for upload: ' . $absoluteLocalPath);
        }

        if (static::isLocal()) {
            $dest = static::disk()->path($relativePath);
            $dir = dirname($dest);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            copy($absoluteLocalPath, $dest);

            return;
        }

        try {
            $ok = static::disk()->put($relativePath, file_get_contents($absoluteLocalPath), static::UPLOAD_OPTIONS);
        } catch (\Throwable $e) {
            static::fail('Could not save the generated file to storage.', $e);
        }

        if ($ok === false || !static::exists($relativePath)) {
            static::fail('The generated file was not saved to storage. Check bucket permissions on Laravel Cloud.');
        }
    }

    /**
     * @return never
     */
    private static function fail(string $message, ?\Throwable $previous = null): void
    {
        if ($previous !== null) {
            report($previous);
        }

        throw new UploadStorageException($message, 0, $previous);
    }

    public static function inlineResponse(string $path, string $displayName, ?string $mimeType = null): BinaryFileResponse|StreamedResponse
    {
        if (!static::exists($path)) {
            abort(404, 'File not found');
        }

        $mimeType ??= static::mimeType($path) ?? 'application/octet-stream';
        $disposition = 'inline; filename="' . str_replace('"', '', $displayName) . '"';

        if (static::isLocal()) {
            return response()->file(static::disk()->path($path), [
                'Content-Type' => $mimeType,
                'Content-Disposition' => $disposition,
            ]);
        }

        return static::disk()->response($path, $displayName, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => $disposition,
        ]);
    }

    /**
     * Attachment download with a user-facing filename (works on local disk and S3/R2 on Laravel Cloud).
     */
    public static function downloadResponse(string $path, string $downloadName): BinaryFileResponse|StreamedResponse
    {
        if (!static::exists($path)) {
            abort(404, 'File not found');
        }

        $downloadName = DocumentNaming::sanitizeDownloadFilename($downloadName);
        $mimeType = static::mimeType($path) ?? 'application/octet-stream';

        if (static::isLocal()) {
            return response()->download(static::disk()->path($path), $downloadName, [
                'Content-Type' => $mimeType,
            ]);
        }

        return response()->streamDownload(function () use ($path) {
            $stream = static::disk()->readStream($path);
            if ($stream === false) {
                abort(404, 'File not found');
            }

            fpassthru($stream);

            if (is_resource($stream)) {
                fclose($stream);
            }
        }, $downloadName, [
            'Content-Type' => $mimeType,
        ]);
    }

    /** Local absolute path — only when upload disk is local (generators). */
    public static function localAbsolutePath(string $relativePath): string
    {
        if (!static::isLocal()) {
            throw new \RuntimeException('localAbsolutePath() is only available when FILESYSTEM_UPLOAD_DISK=local');
        }

        return static::disk()->path($relativePath);
    }
}
