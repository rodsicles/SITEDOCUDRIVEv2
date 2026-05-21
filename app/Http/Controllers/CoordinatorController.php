<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employee;
use App\Models\Task;
use App\Models\DashboardLog;
use App\Models\Document;
use App\Models\DocumentView;
use App\Services\DashboardService;
use App\Services\DocumentService;
use App\Services\FolderService;
use App\Services\ExamRecordService;
use App\Services\TaskService;
use App\Services\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CoordinatorController extends Controller
{
    use \App\Http\Controllers\Concerns\HandlesUploadExceptions;
    use \App\Http\Controllers\Concerns\ValidatesDocumentUpload;
    use \App\Http\Controllers\Concerns\ManagesUserNotifications;

    public function __construct(
        protected DashboardService $dashboardService,
        protected DocumentService $documentService,
        protected TaskService $taskService,
        protected EmployeeService $employeeService,
        protected FolderService $folderService,
        protected ExamRecordService $examRecordService
    ) {}

    /**
     * Get the authenticated coordinator's department.
     */
    private function getCoordinatorDepartment(): ?string
    {
        return optional(auth()->user()->employee)->department;
    }

    /**
     * Scope a user query to only include faculty from the coordinator's department.
     */
    private function scopedFacultyQuery()
    {
        $dept = $this->getCoordinatorDepartment();

        $query = User::with('employee')
            ->where('role_id', 3);

        if ($dept) {
            $query->whereHas('employee', function ($q) use ($dept) {
                $q->where('department', $dept);
            });
        }

        return $query;
    }

    /**
     * Verify that a faculty employee belongs to the coordinator's department.
     */
    private function verifyDepartmentAccess(Employee $employee): void
    {
        $dept = $this->getCoordinatorDepartment();

        if ($dept && $employee->department && $employee->department !== $dept) {
            abort(403, 'You do not have access to faculty members outside your department.');
        }
    }

    public function dashboard()
    {
        $user = auth()->user();
        $stats = $this->dashboardService->getCoordinatorStats($user->id);

        $recentTasks = Task::with(['assignedBy.employee'])
            ->where('assigned_to', $user->id)
            ->latest()
            ->take(5)
            ->get();

        $facultyList = $this->scopedFacultyQuery()
            ->take(10)
            ->get();

        $recentActivities = $this->dashboardService->getRecentActivities($user, 10);
        $announcements = $this->dashboardService->getAnnouncements($user, 5);
        $docAnalyticsData = $this->dashboardService->getCoordinatorDocumentAnalytics($user->id);
        $examTrends = $this->examRecordService->getTrends();

        return view('coordinator.dashboard', array_merge($stats, $docAnalyticsData, compact(
            'recentTasks',
            'facultyList',
            'recentActivities',
            'announcements',
            'examTrends'
        )));
    }

    public function activityLog()
    {
        $activities = DashboardLog::getPaginatedLogs(auth()->user(), 20);
        return view('activity-log', compact('activities'));
    }

    public function tasks()
    {
        $tasks = Task::with(['assignedBy.employee', 'attachments.uploader.employee'])
            ->where('assigned_to', auth()->id())
            ->latest('created_at')
            ->paginate(15);
        return view('coordinator.tasks', compact('tasks'));
    }

    public function updateTask(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:Pending,In Progress,Completed',
        ]);

        $this->taskService->updateTaskByAssignee($id, $validated['status'], auth()->id());

        return redirect()->back()->with('success', 'Task status updated successfully');
    }

    protected function notificationsView(): string
    {
        return 'coordinator.notifications';
    }

    public function faculty()
    {
        $facultyMembers = $this->scopedFacultyQuery()
            ->latest('created_at')
            ->paginate(15);
        return view('coordinator.faculty', compact('facultyMembers'));
    }

    public function createFaculty()
    {
        $dept = $this->getCoordinatorDepartment();
        $nextFacultyNo = $dept
            ? app(\App\Support\EmployeeNumberGenerator::class)->next($dept, \App\Support\EmployeeNumberGenerator::ROLE_FACULTY)
            : '';

        return view('coordinator.create-faculty', compact('nextFacultyNo', 'dept'));
    }

    public function storeFaculty(Request $request)
    {
        $coordDept = $this->getCoordinatorDepartment();

        $validated = $request->validate([
            'username' => 'required|string|unique:users,username|max:20',
            'password' => 'required|string|min:8|max:40',
            'full_name' => 'required|string|max:45',
            'department' => 'required|in:Engineering,Information Technology',
        ]);

        // Enforce coordinator can only create faculty in their own department
        if ($coordDept && $validated['department'] !== $coordDept) {
            return back()->withErrors(['department' => 'You can only create faculty members in your department (' . $coordDept . ').'])
                ->withInput();
        }

        try {
            $this->employeeService->createFaculty($validated, auth()->id());

            return redirect()->route('coordinator.faculty')
                ->with('success', 'Faculty account created successfully');
        } catch (\Exception $e) {
            \Log::error('Faculty creation error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to create faculty account. Please try again.'])
                ->withInput();
        }
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

        return view('coordinator.documents', compact(
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
            : "{$count} document(s) uploaded and shared successfully.";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return redirect()->back()->with('success', $message);
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

    public function editFaculty($id)
    {
        $employee = Employee::with(['user.role'])
            ->where('employee_id', $id)
            ->firstOrFail();

        if ($employee->user->role_id !== 3) {
            abort(403, 'Unauthorized access');
        }

        $this->verifyDepartmentAccess($employee);

        return view('coordinator.edit-faculty', compact('employee'));
    }

    public function updateFaculty(Request $request, $id)
    {
        $employee = Employee::with('user')
            ->where('employee_id', $id)
            ->firstOrFail();

        if ($employee->user->role_id !== 3) {
            abort(403, 'Unauthorized access');
        }

        $this->verifyDepartmentAccess($employee);

        $coordDept = $this->getCoordinatorDepartment();

        $validated = $request->validate([
            'full_name' => 'required|string|max:45',
            'employee_no' => 'nullable|string|max:15|regex:/^[0-9]*$/|unique:employees,employee_no,' . $employee->employee_id . ',employee_id',
            'department' => 'required|in:Information Technology,Engineering',
            'email' => 'nullable|email|max:45',
        ]);

        // Enforce coordinator can only set department to their own
        if ($coordDept && $validated['department'] !== $coordDept) {
            return back()->withErrors(['department' => 'You can only assign faculty to your department (' . $coordDept . ').'])
                ->withInput();
        }

        try {
            $this->employeeService->updateFaculty($employee, $validated, auth()->id());

            return redirect()->route('coordinator.faculty-profile', $id)
                ->with('success', 'Faculty information updated successfully');
        } catch (\Exception $e) {
            \Log::error('Faculty update error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to update faculty information. Please try again.'])
                ->withInput();
        }
    }

    public function resetFacultyPassword(Request $request, $id)
    {
        $employee = Employee::with('user')
            ->where('employee_id', $id)
            ->firstOrFail();

        if ($employee->user->role_id !== 3) {
            abort(403, 'Unauthorized access');
        }

        $this->verifyDepartmentAccess($employee);

        $validated = $request->validate([
            'new_password' => 'required|string|min:8|max:40|confirmed',
        ]);

        try {
            $this->employeeService->resetFacultyPassword($employee, $validated['new_password'], auth()->user());

            return redirect()->route('coordinator.faculty-profile', $id)
                ->with('success', 'Password reset successfully. A notification has been sent to the faculty member.');
        } catch (\Exception $e) {
            \Log::error('Password reset error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to reset password. Please try again.']);
        }
    }

    public function viewEmployeeProfile($id)
    {
        $employee = Employee::with(['user.role'])
            ->where('employee_id', $id)
            ->firstOrFail();

        if ($employee->user->role_id !== 3) {
            abort(403, 'Unauthorized access');
        }

        $this->verifyDepartmentAccess($employee);

        $profileData = $this->employeeService->getEmployeeProfileForCoordinator($id);
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

    public function toggleFavorite($id)
    {
        $result = $this->documentService->toggleFavorite($id, auth()->id());
        return response()->json(['success' => true, 'favorited' => $result['favorited'], 'message' => $result['message']]);
    }
}
