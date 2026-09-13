<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentRequestRecipient extends Model
{
    protected $fillable = [
        'document_request_id', 'user_id', 'submitted_document_id', 'status',
        'review_note', 'submitted_at', 'reviewed_at', 'reviewed_by',
    ];

    protected $casts = ['submitted_at' => 'datetime', 'reviewed_at' => 'datetime'];

    public function request() { return $this->belongsTo(DocumentRequest::class, 'document_request_id'); }
    public function recipient() { return $this->belongsTo(User::class, 'user_id'); }
    public function document() { return $this->belongsTo(Document::class, 'submitted_document_id', 'document_id'); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }
}
