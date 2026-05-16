<?php

namespace App\Http\Controllers;

use App\Models\DashboardLog;
use App\Models\Notification;
use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Http\RedirectResponse;
use App\Support\UploadStorage;
use Illuminate\Http\Request;

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

        // NOTE: We rely on `mimes:` (extension) as the primary guard plus a
        // separate forbidden-extension regex below. We deliberately do NOT
        // attach `mimetypes:` here because browsers send inconsistent MIMEs
        // for CSV (often `application/vnd.ms-excel` or `application/octet-stream`)
        // and would otherwise reject legitimate uploads.
        $validated = $request->validate([
            'attachment' => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,csv,txt,jpg,jpeg,png',
        ]);

        // Defence-in-depth: block dangerous double-extension filenames.
        $originalName = $validated['attachment']->getClientOriginalName();
        if (preg_match('/\.(php|phtml|phar|exe|sh|bat|cmd|com|cgi|pl|py|jsp|asp|aspx|htaccess)(\.|$)/i', $originalName)) {
            return back()->with('error', 'Invalid file type.');
        }

        $file = $validated['attachment'];

        $quotaService = app(\App\Services\StorageQuotaService::class);
        if (!$quotaService->hasQuotaForBytes($user->id, (int) ($file->getSize() ?? 0))) {
            return back()->with('error', 'Storage quota exceeded (limit: ' . $quotaService->formatBytes(\App\Services\StorageQuotaService::DEFAULT_QUOTA_BYTES) . ').');
        }

        $storedPath = UploadStorage::store($file, 'task-attachments');

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

        UploadStorage::assertPathInDirectory($attachment->file_path, 'task-attachments');

        return UploadStorage::downloadResponse($attachment->file_path, $attachment->original_name);
    }

    private function canManageTask(Task $task, int $userId, bool $isDeanOrSecretary): bool
    {
        return $isDeanOrSecretary || $task->assigned_to === $userId || $task->assigned_by === $userId;
    }
}