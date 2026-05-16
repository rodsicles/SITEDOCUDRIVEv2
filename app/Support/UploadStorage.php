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
    public static function diskName(): string
    {
        return (string) config('filesystems.upload_disk', 'local');
    }

    public static function disk(): Filesystem
    {
        return Storage::disk(static::diskName());
    }

    public static function isLocal(): bool
    {
        return static::diskName() === 'local';
    }

    public static function assertSafeRelativePath(string $path): void
    {
        if ($path === '' || str_contains($path, '..') || str_contains($path, './') || str_starts_with($path, '/')) {
            abort(403, 'Invalid file path');
        }
    }

    public static function assertPathInDirectory(string $path, string $directory): void
    {
        static::assertSafeRelativePath($path);
        $prefix = rtrim($directory, '/') . '/';
        if (!str_starts_with($path, $prefix)) {
            abort(403, 'Unauthorized file access');
        }
    }

    /**
     * Path traversal check: realpath on local disk, prefix check on cloud disks.
     */
    public static function assertResolvedPath(string $path, string $directory): void
    {
        static::assertSafeRelativePath($path);

        if (static::isLocal()) {
            $allowedDir = static::disk()->path($directory);
            $realAllowed = realpath($allowedDir);
            $realFile = realpath(static::disk()->path($path));
            if (!$realAllowed || !$realFile || !str_starts_with($realFile, $realAllowed)) {
                abort(403, 'Unauthorized file access');
            }

            return;
        }

        static::assertPathInDirectory($path, $directory);
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
        static::disk()->putFileAs($directory, $file, $name);

        return rtrim($directory, '/') . '/' . $name;
    }

    public static function store(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, static::diskName());
    }

    public static function storeAs(UploadedFile $file, string $directory, string $name): string
    {
        $file->storeAs($directory, $name, static::diskName());

        return rtrim($directory, '/') . '/' . $name;
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

        static::disk()->put($relativePath, file_get_contents($absoluteLocalPath), ['visibility' => 'private']);
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

    public static function downloadResponse(string $path, string $downloadName): StreamedResponse
    {
        if (!static::exists($path)) {
            abort(404, 'File not found');
        }

        return static::disk()->download($path, $downloadName);
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
