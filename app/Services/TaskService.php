<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Notification;
use App\Models\DashboardLog;
use App\Models\User;
use Illuminate\Support\Collection;

class TaskService
{
    /**
     * Create a new task with notification and logging.
     */
    public function createTask(array $validated, int $assignedByUserId): Task
    {
        $task = Task::create([
            'assigned_by' => $assignedByUserId,
            'assigned_to' => $validated['assigned_to'],
            'task_title' => $validated['task_title'],
            'task_description' => $validated['task_description'],
            'due_date' => $validated['due_date'],
            'status' => 'Pending',
        ]);

        Notification::create([
            'user_id' => $validated['assigned_to'],
            'message' => 'New task assigned: ' . $validated['task_title'],
        ]);

        DashboardLog::create([
            'user_id' => $assignedByUserId,
            'target_user_id' => $validated['assigned_to'],
            'activity' => 'Created task: ' . $validated['task_title'],
            'activity_type' => 'task_created',
            'visibility' => 'coordinator',
        ]);

        return $task;
    }

    /**
     * Update task status (by coordinator).
     */
    public function updateTaskByCoordinator(int $taskId, string $status, int $coordinatorId): Task
    {
        $task = Task::where('task_id', $taskId)
            ->where('assigned_by', $coordinatorId)
            ->firstOrFail();

        $task->update(['status' => $status]);

        return $task;
    }

    /**
     * Update task status (by faculty) with notification and logging.
     */
    public function updateTaskByFaculty(int $taskId, string $status, int $facultyUserId): Task
    {
        $task = Task::where('task_id', $taskId)
            ->where('assigned_to', $facultyUserId)
            ->firstOrFail();

        $task->update(['status' => $status]);

        Notification::create([
            'user_id' => $task->assigned_by,
            'message' => 'Task "' . $task->task_title . '" status updated to: ' . $status,
        ]);

        DashboardLog::create([
            'user_id' => $facultyUserId,
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
