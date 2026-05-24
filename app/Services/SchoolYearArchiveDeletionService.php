<?php

namespace App\Services;

use App\Models\DashboardLog;
use App\Models\Document;
use App\Models\DocumentComment;
use App\Models\DocumentFavorite;
use App\Models\DocumentView;
use App\Models\ExamQuestionnaire;
use App\Models\ExamRecord;
use App\Models\Folder;
use App\Models\SchoolYear;
use App\Models\TeachingGuide;
use App\Models\User;
use App\Support\UploadStorage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SchoolYearArchiveDeletionService
{
    /**
     * Permanently delete an archived school year and all records/files tagged to it.
     *
     * @return array<string, int>
     */
    public function permanentlyDeleteArchived(SchoolYear $schoolYear, User $dean): array
    {
        if (!config('school_year.allow_archive_hard_delete', false)) {
            abort(403, 'Permanent archive deletion is disabled on this environment.');
        }

        if (!$schoolYear->isArchived()) {
            abort(403, 'Only archived school years can be permanently deleted.');
        }

        if ($schoolYear->is_active) {
            abort(403, 'Cannot delete the active school year.');
        }

        return $this->purgeSchoolYearBucket(
            $schoolYear,
            $dean,
            "Permanently deleted archived school year and all tagged data: {$schoolYear->name}",
            'archive_deleted',
        );
    }

    /**
     * Remove a school year bucket and all records/files tagged to it.
     *
     * @return array<string, int>
     */
    public function purgeSchoolYearBucket(
        SchoolYear $schoolYear,
        User $dean,
        string $logActivity,
        string $logType = 'archive_deleted',
    ): array {
        $syId = (int) $schoolYear->id;

        $summary = [
            'documents' => 0,
            'teaching_guides' => 0,
            'exam_questionnaires' => 0,
            'exam_records' => 0,
            'folders' => 0,
        ];

        DB::transaction(function () use ($syId, $dean, $schoolYear, $logActivity, $logType, &$summary) {
            $folderIds = Folder::query()
                ->where('school_year_id', $syId)
                ->pluck('folder_id')
                ->all();

            $summary['documents'] = $this->deleteDocumentsForSchoolYear($syId, $folderIds);
            $summary['teaching_guides'] = $this->deleteTeachingGuidesForSchoolYear($syId, $folderIds);
            $summary['exam_questionnaires'] = $this->deleteExamQuestionnairesForSchoolYear($syId);

            if ($folderIds !== []) {
                $summary['exam_records'] = ExamRecord::query()
                    ->whereIn('folder_id', $folderIds)
                    ->delete();
            }

            $summary['folders'] = $this->deleteFoldersForSchoolYear($syId);

            DashboardLog::create([
                'user_id' => $dean->id,
                'target_user_id' => null,
                'activity' => $logActivity,
                'activity_type' => $logType,
                'visibility' => 'dean',
            ]);

            $schoolYear->delete();
        });

        return $summary;
    }

    /**
     * @param  list<int>  $folderIds
     */
    protected function deleteDocumentsForSchoolYear(int $syId, array $folderIds): int
    {
        $count = 0;

        $query = Document::withTrashed()->where(function ($q) use ($syId, $folderIds) {
            $q->where('school_year_id', $syId);
            if ($folderIds !== []) {
                $q->orWhereIn('folder_id', $folderIds);
            }
        });

        $query->orderBy('document_id')->chunkById(50, function ($documents) use (&$count) {
            foreach ($documents as $document) {
                $this->purgeDocument($document);
                $count++;
            }
        }, 'document_id');

        return $count;
    }

    /**
     * @param  list<int>  $folderIds
     */
    protected function deleteTeachingGuidesForSchoolYear(int $syId, array $folderIds): int
    {
        $count = 0;

        TeachingGuide::query()
            ->where(function ($q) use ($syId, $folderIds) {
                $q->where('school_year_id', $syId);
                if ($folderIds !== []) {
                    $q->orWhereIn('folder_id', $folderIds);
                }
            })
            ->orderBy('id')
            ->chunkById(50, function ($rows) use (&$count) {
                foreach ($rows as $row) {
                    $this->deleteStoredFile($row->file_path);
                    $row->delete();
                    $count++;
                }
            });

        return $count;
    }

    protected function deleteExamQuestionnairesForSchoolYear(int $syId): int
    {
        $count = 0;

        ExamQuestionnaire::query()
            ->where('school_year_id', $syId)
            ->orderBy('id')
            ->chunkById(50, function ($rows) use (&$count) {
                foreach ($rows as $row) {
                    $this->deleteStoredFile($row->file_path);
                    $row->delete();
                    $count++;
                }
            });

        return $count;
    }

    protected function deleteFoldersForSchoolYear(int $syId): int
    {
        $deleted = 0;
        $remaining = Folder::query()->where('school_year_id', $syId)->count();

        while ($remaining > 0) {
            $batch = Folder::query()
                ->where('school_year_id', $syId)
                ->orderByDesc('level')
                ->limit(100)
                ->get();

            if ($batch->isEmpty()) {
                break;
            }

            foreach ($batch as $folder) {
                $folder->delete();
                $deleted++;
            }

            $remaining = Folder::query()->where('school_year_id', $syId)->count();
        }

        return $deleted;
    }

    protected function purgeDocument(Document $document): void
    {
        $documentId = $document->document_id;

        DB::table('document_recipients')->where('document_id', $documentId)->delete();
        DocumentComment::query()->where('document_id', $documentId)->delete();
        DocumentView::query()->where('document_id', $documentId)->delete();
        DocumentFavorite::query()->where('document_id', $documentId)->delete();

        ExamRecord::query()->where('document_id', $documentId)->update(['document_id' => null]);

        if (Schema::hasColumn('exam_questionnaires', 'document_id')) {
            ExamQuestionnaire::query()->where('document_id', $documentId)->update(['document_id' => null]);
        }

        if (Schema::hasColumn('teaching_guides', 'document_id')) {
            TeachingGuide::query()->where('document_id', $documentId)->update(['document_id' => null]);
        }

        $this->deleteStoredFile($document->file_path);
        $document->forceDelete();
    }

    protected function deleteStoredFile(?string $path): void
    {
        if (!$path) {
            return;
        }

        try {
            if (UploadStorage::exists($path)) {
                UploadStorage::delete($path);
            }
        } catch (\Throwable) {
            // Continue DB cleanup even if storage is unavailable.
        }
    }
}
