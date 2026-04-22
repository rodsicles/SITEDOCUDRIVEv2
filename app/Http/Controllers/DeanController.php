<?php

namespace App\Http\Controllers;

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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DeanController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
        protected DocumentService $documentService,
        protected EmployeeService $employeeService,
        protected FolderService $folderService,
        protected ExamRecordService $examRecordService,
        protected TaskService $taskService
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
            'recentTasks'
        )));
    }

    public function activityLog()
    {
        $activities = DashboardLog::getPaginatedLogs(auth()->user(), 20);
        return view('activity-log', compact('activities'));
    }

    public function employees()
    {
        $employees = Employee::with('user.role')->latest('created_at')->paginate(15);
        return view('dean.employees', compact('employees'));
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
        $tasks = Task::with(['assignedTo.employee', 'assignedBy.employee'])
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
        ]);

        $this->taskService->createTask($validated, auth()->id());

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

        $folderTree = $this->folderService->getSystemFolderTree();
        $currentFolder = $folderFilter && $folderFilter !== 'uncategorized'
            ? \App\Models\Folder::with('parent.parent')->find($folderFilter)
            : null;
        $breadcrumbs = $currentFolder ? $currentFolder->getAncestors() : [];

        $documents = $this->documentService->getFilteredDocuments(
            auth()->user(), $categoryFilter, $folderFilter, $request->query()
        );
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

        return view('dean.documents', compact(
            'documents', 'categories', 'categoryFilter', 'folderFilter',
            'folderTree', 'currentFolder', 'breadcrumbs', 'tab',
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
        return $this->documentService->viewDocument($id, auth()->user());
    }

    public function downloadDocument($id)
    {
        return $this->documentService->downloadDocument($id, auth()->user());
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
            'email' => 'required|email|unique:users,email|max:45',
            'password' => 'required|string|min:8|max:40',
            'full_name' => 'required|string|max:45',
            'employee_no' => 'nullable|string|unique:employees,employee_no|max:15|regex:/^[0-9]*$/',
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
            'email' => 'required|email|unique:users,email|max:45',
            'password' => 'required|string|min:8|max:40',
            'full_name' => 'required|string|max:45',
            'employee_no' => 'nullable|string|unique:employees,employee_no|max:15|regex:/^[0-9]*$/',
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
