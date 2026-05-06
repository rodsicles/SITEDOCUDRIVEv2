<?php

namespace App\Http\Controllers;

use App\Models\DashboardLog;
use App\Models\Notification;
use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TaskAttachmentController extends Controller
{
    public function store(Request $request, int $id): RedirectResponse
    {
        $task = Task::with(['assignedBy', 'assignedTo'])->findOrFail($id);
        $user = auth()->user();

        if (!$this->canManageTask($task, $user->id, $user->isDeanOrSecretary())) {
            abort(403);
        }

        // Cap attachments per task to prevent storage spam.
        $maxAttachmentsPerTask = 10;
        if (TaskAttachment::where('task_id', $task->task_id)->count() >= $maxAttachmentsPerTask) {
            return back()->with('error', "Maximum of {$maxAttachmentsPerTask} attachments per task reached.");
        }

        $validated = $request->validate([
            'attachment' => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,csv,txt,jpg,jpeg,png|mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv,text/plain,image/jpeg,image/png',
        ]);

        $file = $validated['attachment'];

        $quotaService = app(\App\Services\StorageQuotaService::class);
        if (!$quotaService->hasQuotaForBytes($user->id, (int) ($file->getSize() ?? 0))) {
            return back()->with('error', 'Storage quota exceeded (limit: ' . $quotaService->formatBytes(\App\Services\StorageQuotaService::DEFAULT_QUOTA_BYTES) . ').');
        }

        $storedPath = $file->store('task-attachments', 'local');

        TaskAttachment::create([
            'task_id' => $task->task_id,
            'uploaded_by' => $user->id,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $storedPath,
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
        ]);

        $notifyUserId = $user->id === $task->assigned_by ? $task->assigned_to : $task->assigned_by;
        if ($notifyUserId && $notifyUserId !== $user->id) {
            Notification::create([
                'user_id' => $notifyUserId,
                'message' => 'New attachment added to task: ' . $task->task_title,
            ]);
        }

        DashboardLog::create([
            'user_id' => $user->id,
            'target_user_id' => $notifyUserId,
            'activity' => 'Added task attachment to: ' . $task->task_title,
            'activity_type' => 'task_attachment',
            'visibility' => 'own',
        ]);

        return back()->with('success', 'Task attachment uploaded successfully.');
    }

    public function download(int $id)
    {
        $attachment = TaskAttachment::with('task')->findOrFail($id);
        $user = auth()->user();

        if (!$attachment->canAccess($user)) {
            abort(403);
        }

        if (!Storage::disk('local')->exists($attachment->file_path)) {
            abort(404, 'Attachment file not found.');
        }

        return Storage::disk('local')->download($attachment->file_path, $attachment->original_name);
    }

    private function canManageTask(Task $task, int $userId, bool $isDeanOrSecretary): bool
    {
        return $isDeanOrSecretary || $task->assigned_to === $userId || $task->assigned_by === $userId;
    }
}