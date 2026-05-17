<?php

use App\Models\Document;
use App\Models\Folder;
use App\Services\TeachingGuideSyncService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('document_recipients')) {
            return;
        }

        $sync = app(TeachingGuideSyncService::class);

        Document::query()
            ->where('category', 'Teaching Guides')
            ->whereNotNull('folder_id')
            ->whereDoesntHave('teachingGuide')
            ->whereIn('document_type', ['pdf', 'word'])
            ->chunkById(50, function ($documents) use ($sync) {
                foreach ($documents as $document) {
                    $folder = Folder::find($document->folder_id);
                    if (!$folder) {
                        continue;
                    }

                    $recipientIds = $document->recipients()->pluck('users.id')->all();
                    $sync->syncFromDocument($document, $folder, $recipientIds, $document->document_title);
                }
            }, 'document_id');
    }

    public function down(): void
    {
        // Non-destructive backfill; no rollback.
    }
};
