<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamRecord extends Model
{
    public const PRC_TYPES = ['Civil Engineer', 'Environmental Sanitary Engineering'];
    public const CERT_TYPES = ['Cybersecurity', 'Networking', 'HTML & CSS'];

    public const PRC_FOLDER_SLUG = 'prc-results-civil-sanitary';
    public const CERT_FOLDER_SLUGS = ['cert-cybersecurity', 'cert-networking', 'cert-html-css'];

    protected $fillable = [
        'folder_id',
        'exam_type',
        'batch_label',
        'passed_count',
        'passer_names',
        'total_examinees',
        'recorded_by',
        'document_id',
    ];

    protected $casts = [
        'passer_names' => 'array',
    ];

    public function folder()
    {
        return $this->belongsTo(Folder::class, 'folder_id', 'folder_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id', 'document_id');
    }

    public function scopeForFolder($query, $folderId)
    {
        return $query->where('folder_id', $folderId);
    }

    public function scopeByExamType($query, $type)
    {
        return $query->where('exam_type', $type);
    }

    public function scopePrc($query)
    {
        return $query->whereIn('exam_type', self::PRC_TYPES);
    }

    public function scopeCertification($query)
    {
        return $query->whereIn('exam_type', self::CERT_TYPES);
    }
}
