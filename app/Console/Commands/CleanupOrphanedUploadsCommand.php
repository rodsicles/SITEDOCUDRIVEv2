<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\ExamQuestionnaire;
use App\Models\TaskAttachment;
use App\Models\TeachingGuide;
use App\Support\UploadStorage;
use Illuminate\Console\Command;

class CleanupOrphanedUploadsCommand extends Command
{
    protected $signature = 'storage:cleanup-orphans {--dry-run : List orphaned records without deleting}';

    protected $description = 'Delete database records whose files no longer exist in storage (e.g. after switching storage providers)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN — no records will be deleted.');
        }

        $totalRemoved = 0;

        $totalRemoved += $this->cleanModel(TeachingGuide::class, 'teaching_guides', 'file_path');
        $totalRemoved += $this->cleanModel(ExamQuestionnaire::class, 'exam_questionnaires', 'file_path');
        $totalRemoved += $this->cleanModel(Document::class, 'documents', 'file_path');
        $totalRemoved += $this->cleanModel(TaskAttachment::class, 'task_attachments', 'file_path');

        $this->newLine();
        if ($dryRun) {
            $this->info("Found {$totalRemoved} orphaned record(s). Re-run without --dry-run to delete them.");
        } else {
            $this->info("Deleted {$totalRemoved} orphaned record(s).");
        }

        return self::SUCCESS;
    }

    private function cleanModel(string $modelClass, string $table, string $column): int
    {
        $dryRun = $this->option('dry-run');
        $removed = 0;

        $this->info("Scanning {$table}...");

        try {
            $rows = $modelClass::whereNotNull($column)->where($column, '!=', '')->get();
        } catch (\Throwable $e) {
            $this->error("  Could not query {$table}: " . $e->getMessage());
            return 0;
        }

        foreach ($rows as $row) {
            $path = $row->{$column};
            try {
                if (!UploadStorage::exists($path)) {
                    $key = $row->getKey();
                    if ($dryRun) {
                        $this->warn("  [orphan] #{$key} → {$path}");
                    } else {
                        $row->delete();
                        $this->warn("  Deleted #{$key} → {$path}");
                    }
                    $removed++;
                }
            } catch (\Throwable $e) {
                $this->error("  Error checking #{$row->getKey()}: " . $e->getMessage());
            }
        }

        $this->line("  {$removed} orphaned record(s) in {$table}.");
        return $removed;
    }
}
