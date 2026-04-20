<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamQuestionnaire extends Model
{
    protected $fillable = [
        'submitted_by',
        'title',
        'file_path',
        'file_type',
        'subject',
        'exam_type',
        'semester',
        'academic_year',
        'status',
        'reviewed_by',
        'reviewed_at',
        'remarks',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
