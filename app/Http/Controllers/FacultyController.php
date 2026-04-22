<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\DashboardLog;
use App\Models\Notification;
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

        return view('faculty.dashboard', array_merge($stats, compact(
            'recentTasks',
            'unreadNotifications',
            'recentNotifications',
            'performanceReports',
            'recentActivities',
            'announcements',
            'examTrends'
        )));
    }

    public function tasks()
    {
        $tasks = Task::with('assignedBy')
            ->where('assigned_to', auth()->id())
            ->latest('created_at')
            ->paginate(15);
        return view('faculty.tasks', compact('tasks'));
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

    public function notifications()
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->latest()
            ->paginate(20);
        return view('faculty.notifications', compact('notifications'));
    }

    public function markNotificationRead($id)
    {
        $this->notificationService->markAsRead($id, auth()->id());
        return redirect()->back();
    }

    public function documents(Request $request)
    {
        $categoryFilter = $request->query('category');
        $folderFilter = $request->query('folder');
        $tab = $request->query('tab', 'accreditation');

        $folderTree = $this->folderService->getSystemFolderTree();
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
            'currentFolder', 'breadcrumbs', 'tab',
            'examRecords', 'isPrcFolder', 'isCertFolder'
        ));
    }

    public function uploadDocument(Request $request)
    {
        $validated = $request->validate([
            'document_title' => 'required|string|max:13',
            'document_type' => 'required|in:pdf,image,word',
            'documents' => 'required|array|max:3',
            'documents.*' => match($request->input('document_type')) {
                'pdf' => 'required|file|max:10240|mimes:pdf|mimetypes:application/pdf',
                'word' => 'required|file|max:10240|mimes:doc,docx',
                default => 'required|file|max:10240|mimes:jpg,jpeg,png|mimetypes:image/jpeg,image/png',
            },
            'tags' => 'nullable|string|max:15',
            'folder_id' => 'required|exists:folders,folder_id',
        ]);

        // Block dangerous file extensions (double-extension attack prevention)
        foreach ($request->file('documents') as $file) {
            $originalName = $file->getClientOriginalName();
            if (preg_match('/\.(php|phtml|phar|exe|sh|bat|cmd|com|cgi|pl|py|jsp|asp|aspx|htaccess)/i', pathinfo($originalName, PATHINFO_FILENAME))) {
                return back()->with('error', 'File contains a forbidden extension in its name.');
            }
        }

        $uploadedCount = $this->documentService->uploadDocuments(
            $validated, $request->file('documents'), auth()->id()
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "$uploadedCount document(s) uploaded successfully"
            ]);
        }

        return redirect()->back()->with('success', "$uploadedCount document(s) uploaded successfully");
    }

    public function viewDocument($id)
    {
        return $this->documentService->viewDocument($id, auth()->user(), true);
    }

    public function downloadDocument($id)
    {
        return $this->documentService->downloadDocument($id, auth()->user());
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
