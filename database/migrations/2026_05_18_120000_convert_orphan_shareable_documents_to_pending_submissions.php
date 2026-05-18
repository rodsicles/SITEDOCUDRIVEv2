<?php

use App\Models\Document;
use App\Models\ExamQuestionnaire;
use App\Models\Folder;
use App\Models\TeachingGuide;
use App\Services\ExamQuestionnaireSyncService;
use App\Services\TeachingGuideSyncService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $eqSync = app(ExamQuestionnaireSyncService::class);
        $tgSync = app(TeachingGuideSyncService::class);

        Document::query()
            ->where('category', 'Exam Questionnaires')
            ->whereDoesntHave('examQuestionnaire')
            ->with('uploader.role')
            ->each(function (Document $document) use ($eqSync) {
                $folder = $document->folder_id ? Folder::find($document->folder_id) : null;
                if (!$folder) {
                    $document->delete();

                    return;
                }

                $uploader = $document->uploader;
                $autoApprove = $uploader && ($uploader->isDean() || $uploader->isSecretary());

                ExamQuestionnaire::create([
                    'submitted_by' => $document->uploaded_by,
                    'title' => $document->document_title,
                    'file_path' => $document->file_path,
                    'file_type' => $document->document_type === 'word' ? 'word' : 'pdf',
                    'subject' => $document->subject ?? $document->document_title,
                    'exam_type' => 'Quiz',
                    'submission_type' => $eqSync->submissionTypeFromFolder($folder),
                    'school_year_id' => $document->school_year_id,
                    'semester' => $eqSync->semesterFromFolder($folder),
                    'academic_year' => $eqSync->academicYearFromFolder($folder),
                    'status' => $autoApprove ? 'approved' : 'pending',
                    'reviewed_by' => $autoApprove ? $document->uploaded_by : null,
                    'reviewed_at' => $autoApprove ? now() : null,
                    'document_id' => $autoApprove ? $document->document_id : null,
                ]);

                if (!$autoApprove) {
                    $document->delete();
                }
            });

        Document::query()
            ->where('category', 'Teaching Guides')
            ->whereDoesntHave('teachingGuide')
            ->with('uploader')
            ->each(function (Document $document) use ($tgSync) {
                $folder = $document->folder_id ? Folder::find($document->folder_id) : null;
                if (!$folder) {
                    $document->delete();

                    return;
                }

                $uploader = $document->uploader;
                $autoApprove = $uploader && ($uploader->isDean() || $uploader->isSecretary());

                if ($autoApprove) {
                    $tgSync->syncFromDocument($document, $folder, [], $document->subject);
                    TeachingGuide::where('document_id', $document->document_id)->update([
                        'status' => 'approved',
                        'reviewed_by' => $document->uploaded_by,
                        'reviewed_at' => now(),
                    ]);

                    return;
                }

                TeachingGuide::create([
                    'user_id' => $document->uploaded_by,
                    'title' => $document->document_title,
                    'file_path' => $document->file_path,
                    'file_type' => $document->document_type === 'word' ? 'word' : 'pdf',
                    'subject' => $document->subject ?? $document->document_title,
                    'folder_id' => $document->folder_id,
                    'school_year_id' => $document->school_year_id,
                    'semester' => $tgSync->semesterFromFolder($folder),
                    'academic_year' => $tgSync->academicYearFromFolder($folder),
                    'status' => 'pending',
                ]);

                $document->delete();
            });

        // Pending linked records should not keep a visible document row.
        ExamQuestionnaire::where('status', 'pending')->whereNotNull('document_id')->update(['document_id' => null]);
        TeachingGuide::where('status', 'pending')->whereNotNull('document_id')->update(['document_id' => null]);

        Document::query()
            ->whereIn('category', ['Teaching Guides', 'Exam Questionnaires'])
            ->where(function ($q) {
                $q->whereHas('examQuestionnaire', fn ($eq) => $eq->where('status', '!=', 'approved'))
                    ->orWhereHas('teachingGuide', fn ($tg) => $tg->where('status', '!=', 'approved'));
            })
            ->delete();
    }

    public function down(): void
    {
        // Irreversible data migration.
    }
};
