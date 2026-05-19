<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\DashboardLog;
use App\Models\PerformanceReport;
use App\Services\DashboardService;
use App\Services\DocumentService;
use App\Services\ExamRecordService;
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
        protected FolderService $folderService,
        protected ExamRecordService $examRecordService
    ) {}

    public function dashboard()
    {
        $user = auth()->user();
        $employee = $user->employee;
        $stats = $this->dashboardService->getFacultyStats($user->id);

        $recentTasks = Task::with('assignedBy')
            ->where('assigned_to', $user->id)
            ->latest()
            ->take(5)
            ->get();

        $unreadNotifications = $this->dashboardService->getUnreadNotificationCount($user->id);
        $recentNotifications = $this->dashboardService->getRecentNotifications($user->id, 5);

        $performanceReports = PerformanceReport::with('evaluator')
            ->where('employee_id', $employee->employee_id)
            ->latest('report_date')
            ->take(5)
            ->get();

        $recentActivities = $this->dashboardService->getRecentActivities($user, 10);
        $announcements = $this->dashboardService->getAnnouncements($user, 5);
        $examTrends = $this->examRecordService->getTrends();

        // Document Quick Stats: folders with doc counts + most recent upload
        $folderStats = $this->folderService->getUserFolders($user->id);
        $latestDocument = \App\Models\Document::where('uploaded_by', $user->id)
            ->latest()
            ->first();

        // Upcoming deadlines: pending/in-progress tasks due within 7 days (incl. overdue)
        $upcomingDeadlines = Task::with('assignedBy')
            ->where('assigned_to', $user->id)
            ->whereIn('status', ['Pending', 'In Progress'])
            ->whereNotNull('due_date')
            ->where('due_date', '<=', now()->addDays(7))
            ->orderBy('due_date')
            ->take(5)
            ->get();

        // Pending items snapshot (for the new "My Pending Items" widget)
        $pendingItems = [
            'tasks'     => Task::where('assigned_to', $user->id)->whereIn('status', ['Pending', 'In Progress'])->count(),
            'overdue'   => Task::where('assigned_to', $user->id)->whereIn('status', ['Pending', 'In Progress'])->whereDate('due_date', '<', today())->count(),
            'unread'    => $unreadNotifications,
            'leaves'    => \App\Models\LeaveRequest::where('user_id', $user->id)->where('status', 'Pending')->count(),
        ];

        // Today's calendar events (visible to this user)
        $todayEvents = \App\Models\CalendarEvent::getEventsForUser(
            $user->id,
            now()->startOfDay(),
            now()->endOfDay()
        );

        return view('faculty.dashboard', array_merge($stats, compact(
            'recentTasks',
            'unreadNotifications',
            'recentNotifications',
            'performanceReports',
            'recentActivities',
            'announcements',
            'examTrends',
            'folderStats',
            'latestDocument',
            'upcomingDeadlines',
            'pendingItems',
            'todayEvents'
        )));
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

        $documents = $this->documentService->getFilteredDocuments(
            auth()->user(), $categoryFilter, $folderFilter, $request->query()
        );
        $recentDocuments = $this->documentService->getRecentDocuments(auth()->id(), 5);
        $favoriteDocuments = $this->documentService->getFavoriteDocuments(auth()->user());
        $categories = $this->documentService->getCategories();
        $uploaders = $this->documentService->getAvailableUploaders(auth()->user());
        $savedFilters = auth()->user()->documentFilters()->latest()->get();
        $examRecords = collect();
        $isPrcFolder = false;
        $isCertFolder = false;
        if ($currentFolder) {
            $isPrcFolder = $currentFolder->slug === \App\Models\ExamRecord::PRC_FOLDER_SLUG;
            $isCertFolder = in_array($currentFolder->slug, \App\Models\ExamRecord::CERT_FOLDER_SLUGS);
            if ($isPrcFolder || $isCertFolder) {
                $examRecords = $this->examRecordService->getFolderExamRecords($currentFolder->folder_id);
            }
        }

        return view('faculty.documents', compact(
            'documents', 'recentDocuments', 'favoriteDocuments', 'categories',
            'categoryFilter', 'folderFilter', 'folderTree', 'uploadableFolders',
            'currentFolder', 'breadcrumbs', 'tab', 'uploaders', 'savedFilters',
            'examRecords', 'isPrcFolder', 'isCertFolder'
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

    public function viewDocument($id)
    {
        try {
            return $this->documentService->viewDocument($id, auth()->user(), true);
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

    public function storeExamRecord(Request $request)
    {
        $folderSlug = $request->input('folder_slug');

        if ($folderSlug === \App\Models\ExamRecord::PRC_FOLDER_SLUG) {
            $request->validate([
                'batch_label' => 'required|string|max:50',
                'ce_passed' => 'required|integer|min:0',
                'ce_total' => 'nullable|integer|min:0',
                'ese_passed' => 'required|integer|min:0',
                'ese_total' => 'nullable|integer|min:0',
                'ce_names' => 'nullable|string',
                'ese_names' => 'nullable|string',
            ]);

            $documentId = $this->examRecordService->storePrcResults($request->only([
                'batch_label', 'ce_passed', 'ce_total', 'ese_passed', 'ese_total', 'ce_names', 'ese_names'
            ]), auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'PRC exam results recorded and document generated.',
                'document_id' => $documentId,
            ]);
        }

        if (in_array($folderSlug, \App\Models\ExamRecord::CERT_FOLDER_SLUGS)) {
            $request->validate([
                'folder_id' => 'required|exists:folders,folder_id',
                'batch_label' => 'required|string|max:50',
                'passed_count' => 'required|integer|min:0',
                'passer_names' => 'nullable|string',
            ]);

            $this->examRecordService->storeCertificationCount(
                $request->input('folder_id'),
                $request->only(['batch_label', 'passed_count', 'passer_names']),
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Certification passer count recorded.',
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Invalid folder type.'], 422);
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
