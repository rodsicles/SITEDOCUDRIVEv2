<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\Notification;
use App\Models\DashboardLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class TaskService
{
    /**
     * Create a new task with notification and logging.
     */
    public function createTask(array $validated, int $assignedByUserId, array $attachments = []): Task
    {
        $task = Task::create([
            'assigned_by' => $assignedByUserId,
            'assigned_to' => $validated['assigned_to'],
            'task_title' => $validated['task_title'],
            'task_description' => $validated['task_description'],
            'due_date' => $validated['due_date'],
            'status' => 'Pending',
        ]);

        foreach ($attachments as $attachment) {
            if (!$attachment instanceof UploadedFile) {
                continue;
            }

            $storedPath = $attachment->store('task-attachments', 'local');

            TaskAttachment::create([
                'task_id' => $task->task_id,
                'uploaded_by' => $assignedByUserId,
                'original_name' => $attachment->getClientOriginalName(),
                'file_path' => $storedPath,
                'mime_type' => $attachment->getClientMimeType(),
                'file_size' => $attachment->getSize(),
            ]);
        }

        Notification::create([
            'user_id' => $validated['assigned_to'],
            'message' => 'New task assigned: ' . $validated['task_title'],
        ]);

        DashboardLog::create([
            'user_id' => $assignedByUserId,
            'target_user_id' => $validated['assigned_to'],
            'activity' => 'Created task: ' . $validated['task_title'],
            'activity_type' => 'task_created',
            'visibility' => 'dean',
        ]);

        if (!empty($attachments)) {
            DashboardLog::create([
                'user_id' => $assignedByUserId,
                'target_user_id' => $validated['assigned_to'],
                'activity' => 'Added task attachment(s) to: ' . $validated['task_title'],
                'activity_type' => 'task_attachment',
                'visibility' => 'dean',
            ]);
        }

        return $task;
    }

    /**
     * Update task status (by Dean — can update any task).
     */
    public function updateTaskByDean(int $taskId, string $status): Task
    {
        $task = Task::where('task_id', $taskId)->firstOrFail();
        $task->update(['status' => $status]);
        return $task;
    }

    /**
     * Update task status (by assignee — coordinator or faculty).
     */
    public function updateTaskByAssignee(int $taskId, string $status, int $userId): Task
    {
        $task = Task::where('task_id', $taskId)
            ->where('assigned_to', $userId)
            ->firstOrFail();

        $task->update(['status' => $status]);

        Notification::create([
            'user_id' => $task->assigned_by,
            'message' => 'Task "' . $task->task_title . '" status updated to: ' . $status,
        ]);

        DashboardLog::create([
            'user_id' => $userId,
            'target_user_id' => $task->assigned_by,
            'activity' => 'Updated task status: "' . $task->task_title . '" to ' . $status,
            'activity_type' => 'task_update',
            'visibility' => 'own',
        ]);

        return $task;
    }

    /**
     * Get task statistics for an employee.
     */
    public function getTaskStats(int $userId): array
    {
        $tasks = Task::where('assigned_to', $userId)->get();

        return [
            'total' => $tasks->count(),
            'completed' => $tasks->where('status', 'Completed')->count(),
            'pending' => $tasks->where('status', 'Pending')->count(),
            'in_progress' => $tasks->where('status', 'In Progress')->count(),
        ];
    }
}
