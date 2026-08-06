<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $primaryKey = 'task_id';
    
    protected $fillable = [
        'assigned_by',
        'assigned_to',
        'task_title',
        'task_description',
        'due_date',
        'status',
        'allow_late_submission',
    ];

    protected $casts = [
        'due_date' => 'date',
        'allow_late_submission' => 'boolean',
    ];

    /**
     * True if the deadline has passed and late submission has not been allowed.
     */
    public function isSubmissionLocked(): bool
    {
        if (!$this->due_date || $this->allow_late_submission) {
            return false;
        }

        return now()->startOfDay()->gt($this->due_date->endOfDay());
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function attachments()
    {
        return $this->hasMany(TaskAttachment::class, 'task_id', 'task_id');
    }
}
