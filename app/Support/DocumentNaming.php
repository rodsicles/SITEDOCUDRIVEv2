<?php

namespace App\Support;

final class DocumentNaming
{
    public const TITLE_MAX_LENGTH = 35;

    /**
     * Build a safe attachment filename from the user-visible title and stored object path.
     */
    public static function downloadFilename(string $displayTitle, string $storedPath): string
    {
        $title = trim($displayTitle);
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

        if ($existingExt !== '' && in_array($existingExt, ['pdf', 'doc', 'docx'], true)) {
            $base = pathinfo($base, PATHINFO_FILENAME);
        }

        return $base . '.' . $ext;
    }

    /**
     * Strip characters that break downloads or paths on Windows/macOS/Linux.
     */
    public static function sanitizeDownloadFilename(string $name): string
    {
        $name = str_replace(["\0", '"', '/', '\\', ':', '*', '?', '<', '>', '|'], '', $name);
        $name = preg_replace('/\s+/', ' ', trim($name)) ?? '';

        if ($name === '' || $name === '.') {
            return 'download';
        }

        return mb_substr($name, 0, 200);
    }
}
