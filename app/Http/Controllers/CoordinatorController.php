<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employee;
use App\Models\Task;
use App\Models\Document;
use App\Models\DocumentView;
use App\Services\DashboardService;
use App\Services\DocumentService;
use App\Services\TaskService;
use App\Services\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CoordinatorController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
        protected DocumentService $documentService,
        protected TaskService $taskService,
        protected EmployeeService $employeeService
    ) {}

    public function dashboard()
    {
        $user = auth()->user();
        $stats = $this->dashboardService->getCoordinatorStats($user->id);

        $recentTasks = Task::with(['assignedTo.employee'])
            ->where('assigned_by', $user->id)
            ->latest()
            ->take(5)
            ->get();

        $facultyList = User::with('employee')
            ->where('role_id', 3)
            ->take(10)
            ->get();

        $recentActivities = $this->dashboardService->getRecentActivities($user, 10);
        $announcements = $this->dashboardService->getAnnouncements($user, 5);

        return view('coordinator.dashboard', array_merge($stats, compact(
            'recentTasks',
            'facultyList',
            'recentActivities',
            'announcements'
        )));
    }

    public function tasks()
    {
        $tasks = Task::with(['assignedTo.employee'])
            ->where('assigned_by', auth()->id())
            ->latest('created_at')
            ->paginate(15);
        return view('coordinator.tasks', compact('tasks'));
    }

    public function createTask()
    {
        $facultyMembers = User::with('employee')
            ->where('role_id', 3)
            ->where('status', 'Active')
            ->get();
        return view('coordinator.create-task', compact('facultyMembers'));
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

        return redirect()->route('coordinator.tasks')
            ->with('success', 'Task created successfully');
    }

    public function updateTask(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:Pending,In Progress,Completed',
        ]);

        $this->taskService->updateTaskByCoordinator($id, $validated['status'], auth()->id());

        return redirect()->back()->with('success', 'Task updated successfully');
    }

    public function faculty()
    {
        $facultyMembers = User::with('employee')
            ->where('role_id', 3)
            ->latest('created_at')
            ->paginate(15);
        return view('coordinator.faculty', compact('facultyMembers'));
    }

    public function createFaculty()
    {
        return view('coordinator.create-faculty');
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

            return redirect()->route('coordinator.faculty')
                ->with('success', 'Faculty account created successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to create faculty account: ' . $e->getMessage()])
                ->withInput();
        }
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

        return view('coordinator.documents', compact('documents', 'recentDocuments', 'favoriteDocuments', 'categories', 'categoryFilter', 'folders', 'folderFilter'));
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
            'folder_id' => 'nullable|exists:folders,folder_id',
        ]);

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

    public function editFaculty($id)
    {
        $employee = Employee::with(['user.role'])
            ->where('employee_id', $id)
            ->firstOrFail();

        if ($employee->user->role_id !== 3) {
            abort(403, 'Unauthorized access');
        }

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

        $validated = $request->validate([
            'full_name' => 'required|string|max:45',
            'employee_no' => 'nullable|string|max:15|regex:/^[0-9]*$/|unique:employees,employee_no,' . $employee->employee_id . ',employee_id',
            'department' => 'required|in:Information Technology,Engineering',
            'email' => 'required|email|max:45|unique:users,email,' . $employee->user_id . ',id',
        ]);

        try {
            $this->employeeService->updateFaculty($employee, $validated, auth()->id());

            return redirect()->route('coordinator.faculty-profile', $id)
                ->with('success', 'Faculty information updated successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update faculty information: ' . $e->getMessage()])
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

        $validated = $request->validate([
            'new_password' => 'required|string|min:8|max:40|confirmed',
        ]);

        try {
            $this->employeeService->resetFacultyPassword($employee, $validated['new_password'], auth()->user());

            return redirect()->route('coordinator.faculty-profile', $id)
                ->with('success', 'Password reset successfully. A notification has been sent to the faculty member.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to reset password: ' . $e->getMessage()]);
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

        $profileData = $this->employeeService->getEmployeeProfileForCoordinator($id);
        return view('employees.profile', $profileData);
    }

    public function viewDocument($id)
    {
        return $this->documentService->viewDocument($id, auth()->user(), true);
    }

    public function downloadDocument($id)
    {
        return $this->documentService->downloadDocument($id, auth()->user());
    }

    public function toggleFavorite($id)
    {
        $result = $this->documentService->toggleFavorite($id, auth()->id());
        return response()->json(['success' => true, 'favorited' => $result['favorited'], 'message' => $result['message']]);
    }
}
