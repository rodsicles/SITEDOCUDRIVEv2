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
     * Create one task per assignee (same title, description, due date, and attachment copies).
     *
     * @param  list<int>  $assigneeUserIds
     * @return list<Task>
     */
    public function createTasksForAssignees(array $validated, int $assignedByUserId, array $assigneeUserIds, array $attachments = []): array
    {
        $assignedBy = User::find($assignedByUserId);
        $created = [];

        foreach ($assigneeUserIds as $assigneeUserId) {
            $payload = array_merge($validated, ['assigned_to' => $assigneeUserId]);
            $created[] = $this->createTask($payload, $assignedByUserId, $attachments, $assignedBy);
        }

        return $created;
    }

    /**
     * Create a new task with notification and logging.
     */
    public function createTask(
        array $validated,
        int $assignedByUserId,
        array $attachments = [],
        ?User $assignedBy = null,
    ): Task {
        $assigneeUserId = (int) $validated['assigned_to'];
        $assignedBy ??= User::find($assignedByUserId);

        return DB::transaction(function () use ($validated, $assigneeUserId, $assignedByUserId, $assignedBy, $attachments) {
            $task = Task::create([
                'assigned_by' => $assignedByUserId,
                'assigned_to' => $assigneeUserId,
                'task_title' => $validated['task_title'],
                'task_description' => $validated['task_description'] ?? null,
                'due_date' => $validated['due_date'],
                'status' => 'Pending',
            ]);

            $attachmentNames = $this->storeTaskAttachments($task, $assignedByUserId, $attachments);

            $assignee = User::with('role')->find($assigneeUserId);

            $this->notificationService->notifyTaskAssigned(
                $assigneeUserId,
                $validated['task_title'],
                $assignedBy,
                $validated['task_description'] ?? null,
                $attachmentNames,
                $assignee,
            );

            DashboardLog::create([
                'user_id' => $assignedByUserId,
                'target_user_id' => $assigneeUserId,
                'activity' => 'Created task: ' . $validated['task_title'],
                'activity_type' => 'task_created',
                'visibility' => 'dean',
            ]);

            if ($attachmentNames !== []) {
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
     * @param  list<UploadedFile|mixed>  $attachments
     * @return list<string> Original file names stored
     */
    protected function storeTaskAttachments(Task $task, int $uploadedByUserId, array $attachments): array
    {
        $names = [];

        foreach ($attachments as $attachment) {
            if (!$attachment instanceof UploadedFile) {
                continue;
            }

            $storedPath = UploadStorage::store($attachment, 'task-attachments');
            $originalName = $attachment->getClientOriginalName();
            $names[] = $originalName;

            TaskAttachment::create([
                'task_id' => $task->task_id,
                'uploaded_by' => $uploadedByUserId,
                'original_name' => $originalName,
                'file_path' => $storedPath,
                'mime_type' => $attachment->getClientMimeType(),
                'file_size' => $attachment->getSize(),
            ]);
        }

        return $names;
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
