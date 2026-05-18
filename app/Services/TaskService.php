<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\Notification;
use App\Models\DashboardLog;
use App\Models\User;
use App\Support\UploadStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TaskService
{
    public function __construct(
        protected NotificationService $notificationService,
    ) {}

    /**
     * Create a new task with notification and logging.
     */
    public function createTask(array $validated, int $assignedByUserId, array $attachments = []): Task
    {
        $assigneeUserId = (int) $validated['assigned_to'];
        $assignedBy = User::find($assignedByUserId);

        return DB::transaction(function () use ($validated, $assigneeUserId, $assignedByUserId, $assignedBy, $attachments) {
            $task = Task::create([
                'assigned_by' => $assignedByUserId,
                'assigned_to' => $assigneeUserId,
                'task_title' => $validated['task_title'],
                'task_description' => $validated['task_description'],
                'due_date' => $validated['due_date'],
                'status' => 'Pending',
            ]);

            foreach ($attachments as $attachment) {
                if (!$attachment instanceof UploadedFile) {
                    continue;
                }

                $storedPath = UploadStorage::store($attachment, 'task-attachments');

                TaskAttachment::create([
                    'task_id' => $task->task_id,
                    'uploaded_by' => $assignedByUserId,
                    'original_name' => $attachment->getClientOriginalName(),
                    'file_path' => $storedPath,
                    'mime_type' => $attachment->getClientMimeType(),
                    'file_size' => $attachment->getSize(),
                ]);
            }

            $this->notificationService->notifyTaskAssigned(
                $assigneeUserId,
                $validated['task_title'],
                $assignedBy,
            );

            DashboardLog::create([
                'user_id' => $assignedByUserId,
                'target_user_id' => $assigneeUserId,
                'activity' => 'Created task: ' . $validated['task_title'],
                'activity_type' => 'task_created',
                'visibility' => 'dean',
            ]);

            if (!empty($attachments)) {
                DashboardLog::create([
                    'user_id' => $assignedByUserId,
                    'target_user_id' => $assigneeUserId,
                    'activity' => 'Added task attachment(s) to: ' . $validated['task_title'],
                    'activity_type' => 'task_attachment',
                    'visibility' => 'dean',
                ]);
            }

            return $task;
        });
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
