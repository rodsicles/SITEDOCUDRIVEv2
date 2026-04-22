<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskAttachment extends Model
{
    use HasFactory;

    protected $primaryKey = 'task_attachment_id';

    protected $fillable = [
        'task_id',
        'uploaded_by',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id', 'task_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function canAccess(User $user): bool
    {
        return $user->isDeanOrSecretary()
            || $this->task->assigned_by === $user->id
            || $this->task->assigned_to === $user->id;
    }
}