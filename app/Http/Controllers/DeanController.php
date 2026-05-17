<?php

namespace App\Http\Controllers;

use App\Models\AnnouncementRead;
use App\Models\DocumentView;
use App\Models\Employee;
use App\Models\DashboardLog;
use App\Models\PerformanceReport;
use App\Models\Task;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\DocumentService;
use App\Services\EmployeeService;
use App\Services\ExamRecordService;
use App\Services\FolderService;
use App\Services\TaskService;
use App\Services\WeeklyInsightService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class DeanController extends Controller
{
    use \App\Http\Controllers\Concerns\HandlesUploadExceptions;
    use \App\Http\Controllers\Concerns\ValidatesDocumentUpload;

    public function __construct(
        protected DashboardService $dashboardService,
        protected DocumentService $documentService,
        protected EmployeeService $employeeService,
        protected FolderService $folderService,
        protected ExamRecordService $examRecordService,
        protected TaskService $taskService,
        protected WeeklyInsightService $weeklyInsightService
    ) {}

    public function dashboard()
    {
        $user = auth()->user();
        $stats = $this->dashboardService->getDeanStats($user->id);
        $monthlyUsage = $this->dashboardService->getMonthlyUsage(date('Y'));
        $monthNames = $this->dashboardService->getMonthNames();
        $recentActivities = $this->dashboardService->getRecentActivities($user, 10);
        $performanceData = $this->dashboardService->getPerformanceData();
        $topPerformers = $this->dashboardService->getTopPerformers(5);
        $announcements = $this->dashboardService->getAnnouncements($user, 5);
        $docAnalyticsData = $this->dashboardService->getDeanDocumentAnalytics();
        $examTrends = $this->examRecordService->getTrends();
        $insight = $this->weeklyInsightService->generateForDean();

        $recentTasks = Task::with(['assignedTo.employee'])
            ->latest()
            ->take(5)
            ->get();

        return view('dean.dashboard', array_merge($stats, $docAnalyticsData, compact(
            'monthlyUsage',
            'monthNames',
            'recentActivities',
            'performanceData',
            'topPerformers',
            'announcements',
            'examTrends',
            'recentTasks',
            'insight'
        )));
    }

    public function activityLog()
    {
        // Activity Log and Audit Trail were merged. Redirect any old links to the unified view.
        return redirect()->route('dean.audit-trail');
    }

    /**
     * Force-refresh the cached weekly insight briefing.
     */
    public function refreshInsight(Request $request)
    {
        Cache::forget('weekly_insight_dean');
        $this->weeklyInsightService->generateForDean();

        return redirect()->route('dean.dashboard')
            ->with('success', 'Weekly briefing refreshed.');
    }

    /**
     * Dean-only audit trail with full filtering and search.
     */
    public function auditTrail(Request $request)
    {
        $filters = $request->validate([
            'q'             => 'nullable|string|max:100',
            'activity_type' => 'nullable|string|max:100',
            'user_id'       => 'nullable|integer|exists:users,id',
            'date_from'     => 'nullable|date',
            'date_to'       => 'nullable|date|after_or_equal:date_from',
        ]);

        $query = $this->buildAuditTrailQuery($filters);
        $logs  = $query->paginate(25)->withQueryString();

        $activityTypes = DashboardLog::query()
            ->whereNotNull('activity_type')
            ->distinct()
            ->orderBy('activity_type')
            ->pluck('activity_type');

        $users = User::with('employee')
            ->where('status', 'Active')
            ->get()
            ->sortBy(fn($u) => $u->employee->full_name ?? $u->username)
            ->values();

        $recentDocReads = DocumentView::with(['user.employee', 'document'])
            ->orderByDesc('viewed_at')
            ->limit(20)
            ->get();

        $recentAnnouncementReads = AnnouncementRead::with(['user.employee', 'announcement'])
            ->orderByDesc('read_at')
            ->limit(20)
            ->get();

        return view('dean.audit-trail', compact(
            'logs', 'activityTypes', 'users', 'filters',
            'recentDocReads', 'recentAnnouncementReads'
        ));
    }

    /**
     * Shared query builder for audit trail listing.
     */
    protected function buildAuditTrailQuery(array $filters)
    {
        $query = DashboardLog::with(['user.employee', 'user.role', 'targetUser.employee'])
            ->latest('log_date');

        if (!empty($filters['activity_type'])) {
            $query->where('activity_type', $filters['activity_type']);
        }

        if (!empty($filters['user_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('user_id', $filters['user_id'])
                  ->orWhere('target_user_id', $filters['user_id']);
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('log_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('log_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['q'])) {
            $term = $filters['q'];
            $query->where(function ($q) use ($term) {
                $q->where('activity', 'like', "%{$term}%")
                  ->orWhereHas('user', function ($uq) use ($term) {
                      $uq->where('username', 'like', "%{$term}%")
                         ->orWhereHas('employee', fn($e) => $e->where('full_name', 'like', "%{$term}%"));
                  })
                  ->orWhereHas('targetUser', function ($uq) use ($term) {
                      $uq->where('username', 'like', "%{$term}%")
                         ->orWhereHas('employee', fn($e) => $e->where('full_name', 'like', "%{$term}%"));
                  });
            });
        }

        return $query;
    }

    public function employees()
    {
        $employees = Employee::with('user.role')->latest('created_at')->paginate(15);
        $generator = app(\App\Support\EmployeeNumberGenerator::class);
        $nextCoordinatorNo = $generator->next(\App\Support\EmployeeNumberGenerator::PREFIX_COORDINATOR);
        $nextFacultyNo = $generator->next(\App\Support\EmployeeNumberGenerator::PREFIX_FACULTY);

        return view('dean.employees', compact('employees', 'nextCoordinatorNo', 'nextFacultyNo'));
    }

    public function reports()
    {
        $reports = PerformanceReport::with(['employee', 'evaluator'])
            ->latest('report_date')
            ->paginate(15);
        return view('dean.reports', compact('reports'));
    }

    public function analytics()
    {
        $data = $this->dashboardService->getAnalyticsData();
        $data['examTrends'] = $this->examRecordService->getTrends();
        return view('dean.analytics', $data);
    }

    public function tasks()
    {
        $tasks = Task::with(['assignedTo.employee', 'assignedBy.employee', 'attachments.uploader.employee'])
            ->latest('created_at')
            ->paginate(15);
        return view('dean.tasks', compact('tasks'));
    }

    public function createTask()
    {
        $assignableUsers = User::with('employee')
            ->whereIn('role_id', [2, 3])
            ->where('status', 'Active')
            ->get()
            ->sortBy(fn($u) => $u->employee->full_name ?? $u->username);
        return view('dean.create-task', compact('assignableUsers'));
    }

    public function storeTask(Request $request)
    {
        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
            'task_title' => 'required|string|max:15',
            'task_description' => 'nullable|string|max:150',
            'due_date' => 'required|date',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,csv,txt,jpg,jpeg,png',
        ]);

        $this->taskService->createTask($validated, auth()->id(), $request->file('attachments', []));

        return redirect()->route('dean.tasks')
            ->with('success', 'Task created successfully');
    }

    public function updateTask(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:Pending,In Progress,Completed',
        ]);

        $this->taskService->updateTaskByDean($id, $validated['status']);

        return redirect()->back()->with('success', 'Task updated successfully');
    }

    public function documents(Request $request)
    {
        $categoryFilter = $request->query('category');
        $folderFilter = $request->query('folder');
        $tab = $request->query('tab', 'accreditation');

        $folderTree = $this->folderService->getSystemFolderTree(auth()->user());
        $currentFolder = $folderFilter && $folderFilter !== 'uncategorized'
            ? \App\Models\Folder::with('parent.parent')->find($folderFilter)
            : null;
        $breadcrumbs = $currentFolder ? $currentFolder->getAncestors() : [];

        $documents = $this->documentService->getFilteredDocuments(
            auth()->user(), $categoryFilter, $folderFilter, $request->query()
        );
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

        return view('dean.documents', compact(
            'documents', 'categories', 'categoryFilter', 'folderFilter',
            'folderTree', 'currentFolder', 'breadcrumbs', 'tab', 'uploaders', 'savedFilters',
            'examRecords', 'isPrcFolder', 'isCertFolder'
        ));
    }

    public function viewEmployeeProfile($id)
    {
        $profileData = $this->employeeService->getEmployeeProfile($id);
        return view('employees.profile', $profileData);
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
        return redirect()->back()->with('success', 'Document deleted successfully.');
    }

    public function uploadDocument(Request $request)
    {
        $validated = $this->validateDocumentUpload($request);
        $recipientIds = $validated['recipient_ids'] ?? [];

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
            $uploadedCount = $this->documentService->uploadDocuments(
                $validated, $files, auth()->id(), $recipientIds
            );
        } catch (\Throwable $e) {
            return $this->uploadFailedResponse($request, $e);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "$uploadedCount document(s) uploaded and shared successfully"
            ]);
        }

        return redirect()->back()->with('success', "$uploadedCount document(s) uploaded and shared successfully");
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

    public function storeCoordinator(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|unique:users,username|max:20',
            'password' => 'required|string|min:8|max:40',
            'full_name' => 'required|string|max:45',
            'department' => 'required|in:Engineering,Information Technology',
        ]);

        try {
            $this->employeeService->createCoordinator($validated, auth()->id());
            return redirect()->route('dean.employees')->with('success', 'Coordinator account created successfully.');
        } catch (\Exception $e) {
            \Log::error('Coordinator creation error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to create coordinator account. Please try again.'])->withInput();
        }
    }

    public function storeFaculty(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|unique:users,username|max:20',
            'password' => 'required|string|min:8|max:40',
            'full_name' => 'required|string|max:45',
            'department' => 'required|in:Engineering,Information Technology',
        ]);

        try {
            $this->employeeService->createFaculty($validated, auth()->id());
            return redirect()->route('dean.employees')->with('success', 'Faculty account created successfully.');
        } catch (\Exception $e) {
            \Log::error('Faculty creation error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to create faculty account. Please try again.'])->withInput();
        }
    }

    public function editEmployee($id)
    {
        $employee = Employee::with(['user.role'])->where('employee_id', $id)->firstOrFail();

        if ($employee->user->role_id === 1) {
            abort(403, 'Cannot edit Dean accounts.');
        }

        return view('dean.edit-employee', compact('employee'));
    }

    public function updateEmployee(Request $request, $id)
    {
        $employee = Employee::with('user')->where('employee_id', $id)->firstOrFail();

        if ($employee->user->role_id === 1) {
            abort(403, 'Cannot edit Dean accounts.');
        }

        $validated = $request->validate([
            'full_name' => 'required|string|max:45',
            'employee_no' => 'nullable|string|max:15|regex:/^[0-9]*$/|unique:employees,employee_no,' . $employee->employee_id . ',employee_id',
            'department' => 'required|in:Engineering,Information Technology',
            'email' => 'required|email|max:45|unique:users,email,' . $employee->user_id . ',id',
        ]);

        try {
            $this->employeeService->updateEmployee($employee, $validated, auth()->id());
            return redirect()->route('dean.employee-profile', $id)->with('success', 'Employee information updated successfully.');
        } catch (\Exception $e) {
            \Log::error('Employee update error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to update employee information.'])->withInput();
        }
    }

    public function resetEmployeePassword(Request $request, $id)
    {
        $employee = Employee::with('user')->where('employee_id', $id)->firstOrFail();

        if ($employee->user->role_id === 1) {
            abort(403, 'Cannot reset Dean password.');
        }

        $validated = $request->validate([
            'new_password' => 'required|string|min:8|max:40|confirmed',
        ]);

        try {
            $this->employeeService->resetEmployeePassword($employee, $validated['new_password'], auth()->user());
            return redirect()->route('dean.employee-profile', $id)->with('success', 'Password reset successfully.');
        } catch (\Exception $e) {
            \Log::error('Password reset error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to reset password.']);
        }
    }
}
