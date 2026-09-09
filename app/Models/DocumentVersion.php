<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentVersion extends Model
{
    /** Versions are immutable — only created_at, never updated_at. */
    public $timestamps = false;

    protected $fillable = [
        'document_id',
        'version_number',
        'document_title',
        'file_path',
        'file_size',
        'document_type',
        'uploaded_by',
        'note',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────────

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id', 'document_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    public function fileSizeFormatted(): string
    {
        $bytes = (int) ($this->file_size ?? 0);
        if ($bytes >= 1_048_576) return round($bytes / 1_048_576, 1) . ' MB';
        if ($bytes >= 1_024)     return round($bytes / 1_024, 1)     . ' KB';
        return $bytes . ' B';
    }
}
