<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A reviewer's revision note on a Teaching Guide or Exam Questionnaire version. */
class SubmissionComment extends Model
{
    protected $fillable = ['submission_type', 'submission_id', 'user_id', 'version', 'location', 'body', 'addressed_at', 'addressed_by'];

    protected $casts = ['addressed_at' => 'datetime', 'version' => 'integer'];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function addressedBy()
    {
        return $this->belongsTo(User::class, 'addressed_by');
    }

    public function isOpen(): bool
    {
        return $this->addressed_at === null;
    }

    public function submission(): TeachingGuide|ExamQuestionnaire|null
    {
        return match ($this->submission_type) {
            TeachingGuide::REVIEW_TYPE => TeachingGuide::find($this->submission_id),
            ExamQuestionnaire::REVIEW_TYPE => ExamQuestionnaire::find($this->submission_id),
            default => null,
        };
    }
}
