<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\DashboardLog;
use App\Models\PerformanceReport;
use App\Services\DashboardService;
use App\Services\DocumentService;
use App\Services\FolderService;
use App\Services\TaskService;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class FacultyController extends Controller
{
    use \App\Http\Controllers\Concerns\HandlesUploadExceptions;
    use \App\Http\Controllers\Concerns\ValidatesDocumentUpload;
    use \App\Http\Controllers\Concerns\ManagesUserNotifications;

    public function __construct(
        protected DashboardService $dashboardService,
        protected DocumentService $documentService,
        protected TaskService $taskService,
        protected NotificationService $notificationService,
        protected FolderService $folderService
    ) {}

    public function dashboard()
    {
        $user = auth()->user();
        $payload = $this->dashboardService->getFacultyCommandCenter($user);

        return view('faculty.dashboard', $payload);
    }

    public function tasks(Request $request)
    {
        $filter = $request->query('filter', 'all');
        $allowed = ['all', 'today', 'week', 'overdue', 'pending', 'completed'];
        if (!in_array($filter, $allowed, true)) {
            $filter = 'all';
        }

        $query = Task::with(['assignedBy.employee', 'attachments.uploader.employee'])
            ->where('assigned_to', auth()->id());

        switch ($filter) {
            case 'today':
                $query->whereDate('due_date', today());
                break;
            case 'week':
                $query->whereBetween('due_date', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()]);
                break;
            case 'overdue':
                $query->whereDate('due_date', '<', today())->where('status', '!=', 'Completed');
                break;
            case 'pending':
                $query->whereIn('status', ['Pending', 'In Progress']);
                break;
            case 'completed':
                $query->where('status', 'Completed');
                break;
        }

        // Counts for chip badges (always against the user's full task set)
        $base = Task::where('assigned_to', auth()->id());
        $counts = [
            'all'       => (clone $base)->count(),
            'today'     => (clone $base)->whereDate('due_date', today())->count(),
            'week'      => (clone $base)->whereBetween('due_date', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()])->count(),
            'overdue'   => (clone $base)->whereDate('due_date', '<', today())->where('status', '!=', 'Completed')->count(),
            'pending'   => (clone $base)->whereIn('status', ['Pending', 'In Progress'])->count(),
            'completed' => (clone $base)->where('status', 'Completed')->count(),
        ];

        $tasks = $query->latest('created_at')->paginate(15)->withQueryString();

        return view('faculty.tasks', compact('tasks', 'filter', 'counts'));
    }

    protected function notificationsView(): string
    {
        return 'faculty.notifications';
    }

    public function updateTaskStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:Pending,In Progress,Completed',
        ]);

        $this->taskService->updateTaskByAssignee($id, $validated['status'], auth()->id());

        return redirect()->back()->with('success', 'Task status updated successfully');
    }

    public function activityLog()
    {
        $activities = DashboardLog::getPaginatedLogs(auth()->user(), 20);
        return view('activity-log', compact('activities'));
    }

    public function documents(Request $request)
    {
        $categoryFilter = $request->query('category');
        $folderFilter = $request->query('folder');
        $tab = $request->query('tab', 'accreditation');

        $folderTree = $this->folderService->getSystemFolderTree(auth()->user());
        $uploadableFolders = $this->folderService->getUploadableFolders();
        $currentFolder = $folderFilter && $folderFilter !== 'uncategorized'
            ? \App\Models\Folder::with('parent.parent')->find($folderFilter)
            : null;
        $breadcrumbs = $currentFolder ? $currentFolder->getAncestors() : [];

        // Scope the list to the active tab so each tab only shows its own files.
        $effectiveCategory = $categoryFilter;
        if ($effectiveCategory === null && $folderFilter === null) {
            $effectiveCategory = $this->documentService->categoryForTab($tab, $folderTree);
        }

        $documents = $this->documentService->getFilteredDocuments(
            auth()->user(), $effectiveCategory, $folderFilter, $request->query()
        );
        $recentDocuments = $this->documentService->getRecentDocuments(auth()->id(), 5);
        $favoriteDocuments = $this->documentService->getFavoriteDocuments(auth()->user());
        $categories = $this->documentService->getCategories();
        $uploaders = $this->documentService->getAvailableUploaders(auth()->user());
        $savedFilters = auth()->user()->documentFilters()->latest()->get();

        return view('faculty.documents', compact(
            'documents', 'recentDocuments', 'favoriteDocuments', 'categories',
            'categoryFilter', 'folderFilter', 'folderTree', 'uploadableFolders',
            'currentFolder', 'breadcrumbs', 'tab', 'uploaders', 'savedFilters'
        ));
    }

    public function uploadDocument(Request $request)
    {
        $validated = $this->validateDocumentUpload($request);
        $recipientIds = $validated['recipient_ids'] ?? [];

        // Block dangerous file extensions (double-extension attack prevention)
        $files = $request->file('documents');
        foreach ($files as $file) {
            $originalName = $file->getClientOriginalName();
            if (preg_match('/\.(php|phtml|phar|exe|sh|bat|cmd|com|cgi|pl|py|jsp|asp|aspx|htaccess)/i', pathinfo($originalName, PATHINFO_FILENAME))) {
                $msg = 'File contains a forbidden extension in its name.';
                return $request->expectsJson()
                    ? response()->json(['success' => false, 'message' => $msg], 422)
                    : back()->with('error', $msg);
            }
        }

        $quotaService = app(\App\Services\StorageQuotaService::class);
        $needed = $quotaService->sumUploadedSizes($files);
        if (!$quotaService->hasQuotaForBytes(auth()->id(), $needed)) {
            $msg = 'Storage quota exceeded (limit: ' . $quotaService->formatBytes(\App\Services\StorageQuotaService::DEFAULT_QUOTA_BYTES) . ').';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->with('error', $msg);
        }

        try {
            $result = $this->documentService->uploadDocuments(
                $validated, $files, auth()->id(), $recipientIds
            );
        } catch (\Throwable $e) {
            return $this->uploadFailedResponse($request, $e);
        }

        $count = $result['count'];
        $message = $result['submitted_for_approval']
            ? "{$count} file(s) submitted for Dean approval."
            : "{$count} document(s) uploaded successfully.";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    public function viewDocument($id, Request $request)
    {
        try {
            if ($request->boolean('stream')) {
                return $this->documentService->viewDocument($id, auth()->user(), true);
            }

            return view('submissions.file-preview', $this->documentService->documentPreviewPage($id, auth()->user()));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function downloadDocument($id, Request $request)
    {
        $format = in_array($request->query('format'), ['pdf', 'word']) ? $request->query('format') : 'word';
        try {
            return $this->documentService->downloadDocument($id, auth()->user(), $format);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function deleteDocument($id)
    {
        $this->documentService->deleteDocument($id, auth()->user());
        return redirect()->back()->with('success', 'Document moved to Recycle Bin.');
    }

    public function renameDocument(\App\Http\Requests\RenameDocumentRequest $request, $id)
    {
        $document = $this->documentService->renameDocument(
            (int) $id,
            auth()->user(),
            $request->validated('document_title')
        );

        return response()->json([
            'success' => true,
            'message' => 'Document renamed successfully.',
            'document_title' => $document->document_title,
        ]);
    }

    public function profile()
    {
        $employee = auth()->user()->employee;
        $performanceReports = PerformanceReport::with('evaluator')
            ->where('employee_id', $employee->employee_id)
            ->latest('report_date')
            ->get();

        return view('faculty.profile', compact('employee', 'performanceReports'));
    }

    public function toggleFavorite($id)
    {
        $result = $this->documentService->toggleFavorite($id, auth()->id());
        return response()->json(['success' => true, 'favorited' => $result['favorited'], 'message' => $result['message']]);
    }
}
