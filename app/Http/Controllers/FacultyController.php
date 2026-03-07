<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Notification;
use App\Models\PerformanceReport;
use App\Services\DashboardService;
use App\Services\DocumentService;
use App\Services\TaskService;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class FacultyController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
        protected DocumentService $documentService,
        protected TaskService $taskService,
        protected NotificationService $notificationService
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

        return view('faculty.dashboard', array_merge($stats, compact(
            'recentTasks',
            'unreadNotifications',
            'recentNotifications',
            'performanceReports',
            'recentActivities',
            'announcements'
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

        $this->taskService->updateTaskByFaculty($id, $validated['status'], auth()->id());

        return redirect()->back()->with('success', 'Task status updated successfully');
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

        $folders = $this->documentService->getUserFolders(auth()->id());
        $documents = $this->documentService->getFilteredDocuments(
            auth()->user(), $categoryFilter, $folderFilter, $request->query()
        );
        $recentDocuments = $this->documentService->getRecentDocuments(auth()->id(), 5);
        $favoriteDocuments = $this->documentService->getFavoriteDocuments(auth()->user());
        $categories = $this->documentService->getCategories();

        return view('faculty.documents', compact('documents', 'recentDocuments', 'favoriteDocuments', 'categories', 'categoryFilter', 'folders', 'folderFilter'));
    }

    public function uploadDocument(Request $request)
    {
        $validated = $request->validate([
            'document_title' => 'required|string|max:13',
            'document_type' => 'required|in:pdf,image',
            'documents' => 'required|array|max:3',
            'documents.*' => $request->input('document_type') === 'pdf'
                ? 'required|file|max:10240|mimes:pdf|mimetypes:application/pdf'
                : 'required|file|max:10240|mimes:jpg,jpeg,png|mimetypes:image/jpeg,image/png',
            'category' => 'required|in:Policies,Forms,Reports,Memos,Research Papers,Other',
            'tags' => 'nullable|string|max:15',
            'folder_id' => [
                'nullable',
                \Illuminate\Validation\Rule::exists('folders', 'folder_id')->where(function ($query) {
                    $query->where('user_id', auth()->id());
                }),
            ],
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
