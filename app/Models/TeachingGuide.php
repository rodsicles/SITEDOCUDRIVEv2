<?php

namespace App\Models;

use App\Models\Concerns\ScopesSubmissionVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TeachingGuide extends Model
{
    use ScopesSubmissionVisibility;

    public const REVIEW_TYPE = 'teaching_guide';

    protected $fillable = [
        'user_id',
        'title',
        'file_path',
        'original_filename',
        'file_type',
        'subject',
        'folder_id',
        'document_id',
        'school_year_id',
        'semester',
        'academic_year',
        'description',
        'status',
        'reviewed_by',
        'reviewed_at',
        'remarks',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function folder()
    {
        return $this->belongsTo(Folder::class, 'folder_id', 'folder_id');
    }

    public function recipients()
    {
        return $this->belongsToMany(User::class, 'teaching_guide_recipients', 'teaching_guide_id', 'user_id')
            ->withTimestamps();
    }

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id', 'document_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function reviewComments()
    {
        return $this->hasMany(SubmissionComment::class, 'submission_id')->where('submission_type', self::REVIEW_TYPE);
    }

    public function reviewViews()
    {
        return $this->hasMany(SubmissionView::class, 'submission_id')->where('submission_type', self::REVIEW_TYPE);
    }

    public function owner()
    {
        return $this->uploader();
    }

    public function isPending(): bool
    {
        return ($this->status ?? 'pending') === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    protected function submissionOwnerColumn(): string
    {
        return 'user_id';
    }

    protected function submissionSubmitterRelation(): string
    {
        return 'uploader';
    }
}
