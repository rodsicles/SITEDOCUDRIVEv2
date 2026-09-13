<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\DocumentContentIndexer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class IndexDocumentContentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct(public int $documentId) {}

    public function handle(DocumentContentIndexer $indexer): void
    {
        $document = Document::find($this->documentId);
        if ($document) {
            $indexer->index($document);
        }
    }
}
