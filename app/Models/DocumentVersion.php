<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVersion extends Model
{
    protected $fillable = [
        'document_id',
        'version_number',
        'document_title',
        'file_path',
        'file_size',
        'document_type',
        'uploaded_by',
        'note',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'file_size' => 'integer',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id', 'document_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUploaderNameAttribute(): string
    {
        $uploader = $this->uploader;

        if (!$uploader) {
            return 'Unknown user';
        }

        return optional($uploader->employee)->full_name ?: $uploader->username;
    }
}
