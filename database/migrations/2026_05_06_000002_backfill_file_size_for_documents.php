<?php

use App\Models\Document;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('documents', 'file_size')) {
            return;
        }

        // Backfill file_size for legacy rows so the per-user storage quota
        // service has accurate totals. Process in chunks to avoid memory blow-ups.
        Document::query()
            ->where(function ($q) {
                $q->whereNull('file_size')->orWhere('file_size', 0);
            })
            ->chunkById(200, function ($documents) {
                foreach ($documents as $doc) {
                    $path = $doc->file_path;
                    if (!$path) {
                        continue;
                    }
                    try {
                        if (Storage::disk('local')->exists($path)) {
                            $doc->file_size = (int) Storage::disk('local')->size($path);
                            $doc->saveQuietly();
                        }
                    } catch (\Throwable $e) {
                        // Skip on any filesystem error; quota check will fall back to estimate.
                    }
                }
            }, 'document_id');
    }

    public function down(): void
    {
        // No-op: backfill cannot be safely reverted.
    }
};
