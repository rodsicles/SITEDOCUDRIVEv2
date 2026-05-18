<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Support\UploadStorage;
use Illuminate\Console\Command;

class PurgeOldTrashedDocuments extends Command
{
    protected $signature = 'recycle-bin:purge {--days=30 : Permanently delete trashed documents older than this many days}';

    protected $description = 'Permanently delete documents that have been in the Recycle Bin longer than the retention period';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $documents = Document::onlyTrashed()
            ->where('deleted_at', '<', $cutoff)
            ->get();

        $count = 0;

        foreach ($documents as $document) {
            if ($document->file_path && UploadStorage::exists($document->file_path)) {
                UploadStorage::delete($document->file_path);
            }

            $document->forceDelete();
            $count++;
        }

        $this->info("Permanently deleted {$count} document(s) trashed before {$cutoff->toDateTimeString()}.");

        return self::SUCCESS;
    }
}
