<?php

use App\Models\ExamQuestionnaire;
use App\Services\ExamQuestionnaireSyncService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('exam_questionnaires', 'document_id')) {
            return;
        }

        $sync = app(ExamQuestionnaireSyncService::class);

        ExamQuestionnaire::query()
            ->where('status', 'approved')
            ->whereNull('document_id')
            ->orderBy('id')
            ->each(function (ExamQuestionnaire $questionnaire) use ($sync) {
                $sync->syncToDocument($questionnaire);
            });
    }

    public function down(): void
    {
        // Non-destructive backfill; no rollback.
    }
};
