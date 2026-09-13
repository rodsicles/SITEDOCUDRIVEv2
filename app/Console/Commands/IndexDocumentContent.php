<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\DocumentSearchIndex;
use App\Services\DocumentContentIndexer;
use Illuminate\Console\Command;

class IndexDocumentContent extends Command
{
    protected $signature = 'documents:index-content {--force : Re-index documents that already have a completed index}';
    protected $description = 'Extract searchable text and hashes from stored documents';

    public function handle(DocumentContentIndexer $indexer): int
    {
        $query = Document::query()->orderBy('document_id');
        if (!$this->option('force')) {
            $query->whereDoesntHave('searchIndex', fn ($q) => $q->whereIn('index_status', ['indexed', 'no_text']));
        }

        $count = (clone $query)->count();
        $bar = $this->output->createProgressBar($count);
        $query->chunkById(25, function ($documents) use ($indexer, $bar) {
            foreach ($documents as $document) {
                $indexer->index($document);
                $bar->advance();
            }
        }, 'document_id');
        $bar->finish();
        $this->newLine();
        $this->info("Indexed {$count} document(s).");
        $summary = DocumentSearchIndex::query()->selectRaw('index_status, COUNT(*) as total')->groupBy('index_status')->pluck('total', 'index_status');
        $this->table(['Status', 'Documents'], $summary->map(fn ($total, $status) => [$status, $total])->values()->all());
        return self::SUCCESS;
    }
}
