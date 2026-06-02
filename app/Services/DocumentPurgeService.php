<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentComment;
use App\Models\DocumentFavorite;
use App\Models\DocumentView;
use App\Models\ExamQuestionnaire;
use App\Models\ExamRecord;
use App\Models\TeachingGuide;
use App\Support\UploadStorage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DocumentPurgeService
{
    public function purge(Document $document, bool $deleteStoredFile = true): void
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

        if ($deleteStoredFile) {
            $this->deleteStoredFile($document->file_path);
        }

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
        } catch (\Throwable $e) {
            // File may already be gone; still remove DB row.
        }
    }
}
