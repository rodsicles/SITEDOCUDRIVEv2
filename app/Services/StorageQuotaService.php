<?php

namespace App\Services;

use App\Models\Document;
use App\Models\TaskAttachment;
use App\Models\TeachingGuide;
use App\Models\ExamQuestionnaire;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Per-user storage quota helper.
 *
 * Sums file_size across uploaded documents and task attachments for a user
 * and rejects new uploads that would exceed the configured cap.
 */
class StorageQuotaService
{
    /** Default per-user storage cap in bytes (500 MB). */
    public const DEFAULT_QUOTA_BYTES = 524288000;

    /**
     * Total bytes currently used by the user's uploads.
     */
    public function usedBytes(int $userId): int
    {
        $taskAttachmentBytes = (int) TaskAttachment::where('uploaded_by', $userId)->sum('file_size');

        $documentBytes = 0;
        if (Schema::hasColumn('documents', 'file_size')) {
            $documentBytes = (int) Document::where('uploaded_by', $userId)->sum('file_size');
        } else {
            // Fallback: estimate by counting documents at avg 5 MB each.
            $documentBytes = (int) Document::where('uploaded_by', $userId)->count() * 5242880;
        }

        $teachingGuideBytes = $this->estimateModelBytes(TeachingGuide::class, 'user_id', $userId);
        $examQuestionnaireBytes = $this->estimateModelBytes(ExamQuestionnaire::class, 'submitted_by', $userId);

        return $taskAttachmentBytes + $documentBytes + $teachingGuideBytes + $examQuestionnaireBytes;
    }

    /**
     * Determine whether the user has enough remaining quota to add the given bytes.
     */
    public function hasQuotaForBytes(int $userId, int $bytesNeeded, ?int $quotaBytes = null): bool
    {
        $quota = $quotaBytes ?? self::DEFAULT_QUOTA_BYTES;
        return ($this->usedBytes($userId) + $bytesNeeded) <= $quota;
    }

    /**
     * Sum bytes for an arbitrary uploaded files array (Symfony UploadedFile).
     *
     * @param array<int, \Illuminate\Http\UploadedFile|null> $files
     */
    public function sumUploadedSizes(array $files): int
    {
        $total = 0;
        foreach ($files as $file) {
            if ($file && method_exists($file, 'getSize')) {
                $total += (int) $file->getSize();
            }
        }
        return $total;
    }

    /**
     * Format bytes into a human-readable string (e.g. "12.34 MB").
     */
    public function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        return round($bytes / (1024 ** $power), $precision) . ' ' . $units[$power];
    }

    /**
     * Best-effort byte estimation for models without a file_size column.
     */
    protected function estimateModelBytes(string $modelClass, string $userColumn, int $userId): int
    {
        if (!class_exists($modelClass)) {
            return 0;
        }

        try {
            $rows = $modelClass::where($userColumn, $userId)->get(['file_path']);
        } catch (\Throwable $e) {
            return 0;
        }

        $bytes = 0;
        foreach ($rows as $row) {
            $path = $row->file_path ?? null;
            if (!$path) {
                continue;
            }
            try {
                if (Storage::disk('local')->exists($path)) {
                    $bytes += (int) Storage::disk('local')->size($path);
                }
            } catch (\Throwable $e) {
                // Ignore filesystem errors; treat as 0.
            }
        }
        return $bytes;
    }
}
