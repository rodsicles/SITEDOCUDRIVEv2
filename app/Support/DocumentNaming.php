<?php

namespace App\Support;

final class DocumentNaming
{
    public const TITLE_MAX_LENGTH = 35;

    /**
     * Prefer the original uploaded name; use the title for legacy/generated files.
     * The actual served object's extension wins (e.g. a converted PDF sibling).
     */
    public static function downloadFilename(string $displayTitle, string $storedPath, ?string $originalFilename = null): string
    {
        $hasOriginal = trim((string) $originalFilename) !== '';
        $title = $hasOriginal ? basename(str_replace('\\', '/', $originalFilename)) : trim($displayTitle);
        if ($title === '') {
            return static::sanitizeDownloadFilename(basename($storedPath));
        }

        $base = static::sanitizeDownloadFilename($title);
        $ext = strtolower(pathinfo($storedPath, PATHINFO_EXTENSION));

        if ($ext === '') {
            return $base;
        }

        $existingExt = strtolower(pathinfo($base, PATHINFO_EXTENSION));
        if ($existingExt === $ext) {
            return $base;
        }

        if ($existingExt !== '' && ($hasOriginal || in_array($existingExt, ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif', 'webp'], true))) {
            $base = pathinfo($base, PATHINFO_FILENAME);
        }

        return $base . '.' . $ext;
    }

    public static function uploadedFilename(\Illuminate\Http\UploadedFile $file): string
    {
        return static::downloadFilename($file->getClientOriginalName(), 'upload.'.$file->getClientOriginalExtension(), $file->getClientOriginalName());
    }

    /**
     * Strip characters that break downloads or paths on Windows/macOS/Linux.
     */
    public static function sanitizeDownloadFilename(string $name): string
    {
        $name = str_replace(["\0", '"', '/', '\\', ':', '*', '?', '<', '>', '|'], '', $name);
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '';
        $name = preg_replace('/\s+/', ' ', trim($name)) ?? '';

        if ($name === '' || $name === '.') {
            return 'download';
        }

        $ext = pathinfo($name, PATHINFO_EXTENSION);
        if (mb_strlen($name) > 200 && $ext !== '' && mb_strlen($ext) < 15) {
            return mb_substr(pathinfo($name, PATHINFO_FILENAME), 0, 199 - mb_strlen($ext)).'.'.$ext;
        }
        return mb_substr($name, 0, 200);
    }

    /**
     * Strip a trailing version marker like " (v2)", " v3", or " - version 4".
     */
    public static function baseTitleWithoutVersion(string $title): string
    {
        $base = preg_replace('/\s*[\(-]?\s*v(?:ersion)?\s*\d+\s*\)?\s*$/i', '', trim($title)) ?? '';
        $base = trim($base);

        return $base !== '' ? $base : 'Untitled';
    }

    /**
     * Rename for a live version, e.g. "GameDevPrelim" → "GameDevPrelim (v2)".
     * Replaces any existing trailing version suffix so titles do not stack.
     */
    public static function titleWithVersion(string $title, int $version): string
    {
        $version = max(1, $version);
        $suffix = ' (v'.$version.')';
        $base = self::baseTitleWithoutVersion($title);
        $maxBase = self::TITLE_MAX_LENGTH - mb_strlen($suffix);
        if ($maxBase < 1) {
            $maxBase = 1;
        }

        return mb_substr($base, 0, $maxBase).$suffix;
    }

    /**
     * Build a document_title from an uploaded file's original name (no extension).
     */
    public static function titleFromUploadedFile(\Illuminate\Http\UploadedFile $file): string
    {
        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $base = preg_replace('/\s+/', ' ', trim((string) $base)) ?? '';
        $base = str_replace(["\0", '"', '/', '\\', ':', '*', '?', '<', '>', '|'], '', $base);

        if ($base === '' || $base === '.') {
            $base = 'Untitled';
        }

        return mb_substr($base, 0, self::TITLE_MAX_LENGTH);
    }
}
