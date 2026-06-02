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
use App\Services\EmployeeAccountDeletionService;
use App\Services\EmployeeService;
use App\Services\FolderService;
use App\Services\TaskService;
use App\Services\WeeklyInsightService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class DeanController extends Controller
{
    use \App\Http\Controllers\Concerns\HandlesUploadExceptions;
    use \App\Http\Controllers\Concerns\ManagesUserNotifications;
    use \App\Http\Controllers\Concerns\ValidatesDocumentUpload;

    public function __construct(
        protected DashboardService $dashboardService,
        protected DocumentService $documentService,
        protected EmployeeService $employeeService,
        protected FolderService $folderService,
        protected TaskService $taskService,
        protected WeeklyInsightService $weeklyInsightService
    ) {}

    public function dashboard()
    {
        $user = auth()->user();
        $stats = $this->dashboardService->getDeanStats($user->id);
        $announcements = $this->dashboardService->getAnnouncements($user, 5);
        $insight = $this->weeklyInsightService->generateForDean();

        $recentTasks = Task::with(['assignedTo.employee'])
            ->latest()
            ->take(5)
            ->get();

        $activeId = \App\Models\SchoolYear::activeId();
        $pendingScope = fn ($q) => $q->where('status', 'pending')
            ->where(function ($q2) use ($activeId) {
                $q2->where('school_year_id', $activeId)->orWhereNull('school_year_id');
            });
        $pendingTeachingGuidesCount = \App\Models\TeachingGuide::query()->where($pendingScope)->count();
        $pendingExamQuestionnairesCount = \App\Models\ExamQuestionnaire::query()->where($pendingScope)->count();
        $pendingApprovals = $pendingTeachingGuidesCount + $pendingExamQuestionnairesCount;

        $docsThisSchoolYear = \App\Models\Document::where(function ($q) use ($activeId) {
            $q->where('school_year_id', $activeId)->orWhereNull('school_year_id');
        })->count();

        $tasksInProgress = Task::where('status', 'In Progress')->count();

        return view('dean.dashboard', array_merge($stats, compact(
            'announcements',
            'recentTasks',
            'insight',
            'pendingTeachingGuidesCount',
            'pendingExamQuestionnairesCount',
            'pendingApprovals',
            'docsThisSchoolYear',
            'tasksInProgress',
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
        $employees = Employee::with('user.role')
            ->whereHas('user', fn ($q) => $q->where('status', 'Active'))
            ->latest('created_at')
            ->paginate(15);

        $deactivatedEmployees = Employee::with('user.role')
            ->whereHas('user', fn ($q) => $q->where('status', 'Inactive'))
            ->latest('updated_at')
            ->paginate(15, ['*'], 'deactivated_page');

        $generator = app(\App\Support\EmployeeNumberGenerator::class);
        $coordinatorNumberPreview = $generator->previewMap(\App\Support\EmployeeNumberGenerator::ROLE_COORDINATOR);
        $facultyNumberPreview = $generator->previewMap(\App\Support\EmployeeNumberGenerator::ROLE_FACULTY);

        return view('dean.employees', compact(
            'employees',
            'deactivatedEmployees',
            'coordinatorNumberPreview',
            'facultyNumberPreview'
        ));
    }

    public function reports()
    {
        $reports = PerformanceReport::with(['employee', 'evaluator'])
            ->latest('report_date')
            ->paginate(15);
        return view('dean.reports', compact('reports'));
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
        $assignableUsers = \App\Support\TaskAssigneeResolver::assignableUsersSorted();

        return view('dean.create-task', compact('assignableUsers'));
    }

    public function storeTask(Request $request)
    {
        $scopes = [
            \App\Support\TaskAssigneeResolver::SCOPE_INDIVIDUAL,
            \App\Support\TaskAssigneeResolver::SCOPE_DEPARTMENT_IT,
            \App\Support\TaskAssigneeResolver::SCOPE_DEPARTMENT_ENGINEERING,
            \App\Support\TaskAssigneeResolver::SCOPE_DEPARTMENT_SITE,
        ];

        $validated = $request->validate([
            'assignment_scope' => 'required|in:' . implode(',', $scopes),
            'assignee_ids' => 'nullable|array',
            'assignee_ids.*' => 'integer',
            'task_title' => 'required|string|max:50',
            'task_description' => 'nullable|string|max:250',
            'due_date' => 'required|date',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,csv,txt,jpg,jpeg,png',
        ]);

        $assigneeIds = \App\Support\TaskAssigneeResolver::resolve(
            $validated['assignment_scope'],
            $validated['assignee_ids'] ?? [],
        );

        if ($assigneeIds === []) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors([
                    'assignee_ids' => $validated['assignment_scope'] === \App\Support\TaskAssigneeResolver::SCOPE_INDIVIDUAL
                        ? 'Select at least one faculty member or coordinator.'
                        : 'No active accounts were found for the selected department group.',
                ]);
        }

        unset($validated['assignment_scope'], $validated['assignee_ids']);

        $created = $this->taskService->createTasksForAssignees(
            $validated,
            auth()->id(),
            $assigneeIds,
            $request->file('attachments', []),
        );

        $count = count($created);
        $message = $count === 1
            ? 'Task created and assigned successfully.'
            : "Task created and assigned to {$count} people.";

        return redirect()->route('dean.tasks')->with('success', $message);
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

        return view('dean.documents', compact(
            'documents', 'categories', 'categoryFilter', 'folderFilter',
            'folderTree', 'currentFolder', 'breadcrumbs', 'tab', 'uploaders', 'savedFilters'
        ));
    }

    public function viewEmployeeProfile($id)
    {
        $profileData = $this->employeeService->getEmployeeProfile($id);
        return view('employees.profile', $profileData);
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
            $result = $this->documentService->uploadDocuments(
                $validated, $files, auth()->id(), $recipientIds
            );
        } catch (\Throwable $e) {
            return $this->uploadFailedResponse($request, $e);
        }

        $count = $result['count'];
        $message = "{$count} document(s) uploaded and shared successfully.";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return redirect()->back()->with('success', $message);
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
            'email' => 'nullable|email|max:45',
        ]);

        try {
            $this->employeeService->updateEmployee($employee, $validated, auth()->id());
            return redirect()->route('dean.employee-profile', $id)->with('success', 'Employee information updated successfully.');
        } catch (\Exception $e) {
            \Log::error('Employee update error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to update employee information.'])->withInput();
        }
    }

    public function deactivateEmployee($id)
    {
        $employee = Employee::with('user')->where('employee_id', $id)->firstOrFail();

        try {
            $this->employeeService->setAccountStatus($employee, 'Inactive', auth()->user());

            return redirect()
                ->route('dean.employees', ['tab' => 'deactivated'])
                ->with('success', $employee->full_name . ' has been deactivated. Their uploads and folders are unchanged.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Account deactivation error: ' . $e->getMessage());

            return back()->withErrors(['error' => 'Failed to deactivate account.']);
        }
    }

    public function reactivateEmployee($id)
    {
        $employee = Employee::with('user')->where('employee_id', $id)->firstOrFail();

        try {
            $this->employeeService->setAccountStatus($employee, 'Active', auth()->user());

            return redirect()
                ->route('dean.employees', ['tab' => 'list'])
                ->with('success', $employee->full_name . ' has been reactivated and can sign in again.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Account reactivation error: ' . $e->getMessage());

            return back()->withErrors(['error' => 'Failed to reactivate account.']);
        }
    }

    public function destroyEmployee(Request $request, $id, EmployeeAccountDeletionService $deletionService)
    {
        $employee = Employee::with(['user.role'])->where('employee_id', $id)->firstOrFail();

        $validated = $request->validate([
            'confirm_username' => 'required|string|max:50',
            'confirm_phrase' => 'required|string|max:50',
        ]);

        if ($validated['confirm_username'] !== $employee->user->username) {
            return back()->withErrors([
                'confirm_username' => 'Username does not match this account.',
            ]);
        }

        if ($validated['confirm_phrase'] !== 'DELETE PERMANENTLY') {
            return back()->withErrors([
                'confirm_phrase' => 'Type DELETE PERMANENTLY in all caps to confirm.',
            ]);
        }

        try {
            $name = $employee->full_name;
            $summary = $deletionService->permanentlyDelete($employee, auth()->user());

            $detail = sprintf(
                'Removed %d document(s), %d teaching guide(s), %d exam questionnaire(s), %d task(s).',
                $summary['documents'],
                $summary['teaching_guides'],
                $summary['exam_questionnaires'],
                $summary['tasks'],
            );

            return redirect()
                ->route('dean.employees')
                ->with('success', "Account for {$name} was permanently deleted. {$detail}");
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Account permanent delete error: ' . $e->getMessage(), [
                'employee_id' => $id,
                'exception' => $e,
            ]);

            return back()->withErrors([
                'error' => 'Permanent delete failed. No changes were saved. Check logs if this persists.',
            ]);
        }
    }

    public function resetEmployeePassword(Request $request, $id)
    {
        $employee = Employee::with('user')->where('employee_id', $id)->firstOrFail();

        if ($employee->user->role_id === 1) {
            abort(403, 'Cannot reset Dean password.');
        }

        if ($employee->user->status !== 'Active') {
            return back()->withErrors(['error' => 'Reactivate this account before resetting the password.']);
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

    protected function notificationsView(): string
    {
        return 'dean.notifications';
    }
}
