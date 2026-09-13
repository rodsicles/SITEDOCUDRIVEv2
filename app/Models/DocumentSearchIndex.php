<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSearchIndex extends Model
{
    protected $table = 'document_search_indexes';

    protected $fillable = ['document_id', 'file_hash', 'content_text', 'extraction_method', 'index_status', 'index_error', 'indexed_at'];
    protected $casts = ['indexed_at' => 'datetime'];
    public function document() { return $this->belongsTo(Document::class, 'document_id', 'document_id'); }
}
