<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employee;
use App\Models\Task;
use App\Models\Notification;
use App\Models\Document;
use App\Models\DocumentView;
use App\Models\DashboardLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CoordinatorController extends Controller
{
    public function dashboard()
    {
        $totalFaculty = User::where('role_id', 3)->count();
        $myTasks = Task::where('assigned_by', auth()->id())->count();
        $pendingTasks = Task::where('assigned_by', auth()->id())
            ->where('status', 'Pending')
            ->count();
        $completedTasks = Task::where('assigned_by', auth()->id())
            ->where('status', 'Completed')
            ->count();

        $recentTasks = Task::with(['assignedTo.employee'])
            ->where('assigned_by', auth()->id())
            ->latest()
            ->take(5)
            ->get();

        $facultyList = User::with('employee')
            ->where('role_id', 3)
            ->take(10)
            ->get();

        // Coordinator sees filtered activities (own + all faculty activities)
        $recentActivities = DashboardLog::getFilteredLogs(auth()->user(), 10);

        return view('coordinator.dashboard', compact(
            'totalFaculty',
            'myTasks',
            'pendingTasks',
            'completedTasks',
            'recentTasks',
            'facultyList',
            'recentActivities'
        ));
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
            'task_title' => 'required|string|max:150',
            'task_description' => 'nullable|string',
            'due_date' => 'required|date',
        ]);

        $task = Task::create([
            'assigned_by' => auth()->id(),
            'assigned_to' => $validated['assigned_to'],
            'task_title' => $validated['task_title'],
            'task_description' => $validated['task_description'],
            'due_date' => $validated['due_date'],
            'status' => 'Pending',
        ]);

        Notification::create([
            'user_id' => $validated['assigned_to'],
            'message' => 'New task assigned: ' . $validated['task_title'],
        ]);

        DashboardLog::create([
            'user_id' => auth()->id(),
            'target_user_id' => $validated['assigned_to'],
            'activity' => 'Created task: ' . $validated['task_title'],
            'activity_type' => 'task_created',
            'visibility' => 'coordinator',
        ]);

        return redirect()->route('coordinator.tasks')
            ->with('success', 'Task created successfully');
    }

    public function updateTask(Request $request, $id)
    {
        $task = Task::where('task_id', $id)
            ->where('assigned_by', auth()->id())
            ->firstOrFail();

        $validated = $request->validate([
            'status' => 'required|in:Pending,In Progress,Completed',
        ]);

        $task->update($validated);

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
            'username' => 'required|string|unique:users,username|max:50',
            'email' => 'required|email|unique:users,email|max:100',
            'password' => 'required|string|min:8',
            'full_name' => 'required|string|max:100',
            'employee_no' => 'nullable|string|unique:employees,employee_no|max:30',
            'department' => 'required|in:Engineering,Information Technology',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'role_id' => 3,
                'name' => $validated['full_name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'status' => 'Active',
            ]);

            Employee::create([
                'user_id' => $user->id,
                'employee_no' => $validated['employee_no'],
                'full_name' => $validated['full_name'],
                'department' => $validated['department'],
                'position' => 'Faculty Employee',
                'hire_date' => now(),
            ]);

            DashboardLog::create([
                'user_id' => auth()->id(),
                'target_user_id' => $user->id,
                'activity' => 'Created faculty account: ' . $validated['full_name'],
                'activity_type' => 'account_created',
                'visibility' => 'coordinator',
            ]);

            DB::commit();
            return redirect()->route('coordinator.faculty')
                ->with('success', 'Faculty account created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create faculty account: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function documents()
    {
        $documents = Document::getFilteredDocuments(auth()->user())->paginate(15);
        $recentDocuments = DocumentView::getRecentDocuments(auth()->id(), 5);
        $favoriteDocuments = auth()->user()->documentFavorites()->with('document')->get()->pluck('document');
        
        return view('coordinator.documents', compact('documents', 'recentDocuments', 'favoriteDocuments'));
    }

    public function uploadDocument(Request $request)
    {
        $validated = $request->validate([
            'document_title' => 'required|string|max:150',
            'document_type' => 'required|in:pdf,image',
            'documents' => 'required|array',
            'documents.*' => $request->input('document_type') === 'pdf'
                ? 'required|file|max:10240|mimes:pdf|mimetypes:application/pdf'
                : 'required|file|max:10240|mimes:jpg,jpeg,png|mimetypes:image/jpeg,image/png',
            'category' => 'required|in:Policies,Forms,Reports,Memos,Research Papers,Other',
            'tags' => 'nullable|string',
        ]);

        // Parse tags
        $tags = !empty($validated['tags']) ? implode(',', array_map('trim', explode(',', $validated['tags']))) : '';

        $uploadedCount = 0;
        foreach ($request->file('documents') as $index => $file) {
            $filename = time() . '_' . $index . '_' . $file->hashName();
            Storage::disk('local')->putFileAs('documents', $file, $filename);

            Document::create([
                'uploaded_by' => auth()->id(),
                'document_title' => $validated['document_title'] . ($uploadedCount > 0 ? ' (' . ($uploadedCount + 1) . ')' : ''),
                'file_path' => 'documents/' . $filename,
                'document_type' => $validated['document_type'],
                'category' => $validated['category'],
                'tags' => $tags,
            ]);
            $uploadedCount++;
        }

        return redirect()->back()->with('success', "$uploadedCount document(s) uploaded successfully");
    }

    public function editFaculty($id)
    {
        $employee = Employee::with(['user.role'])
            ->where('employee_id', $id)
            ->firstOrFail();

        // Only allow editing faculty employees
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

        // Only allow editing faculty employees
        if ($employee->user->role_id !== 3) {
            abort(403, 'Unauthorized access');
        }

        $validated = $request->validate([
            'full_name' => 'required|string|max:100',
            'employee_no' => 'nullable|string|max:30|unique:employees,employee_no,' . $employee->employee_id . ',employee_id',
            'department' => 'required|in:Information Technology,Engineering',
            'email' => 'required|email|max:100|unique:users,email,' . $employee->user_id . ',id',
        ]);

        DB::beginTransaction();
        try {
            // Update employee information
            $employee->update([
                'full_name' => $validated['full_name'],
                'employee_no' => $validated['employee_no'],
                'department' => $validated['department'],
            ]);

            // Update user information
            $employee->user->update([
                'name' => $validated['full_name'],
                'email' => $validated['email'],
            ]);

            DashboardLog::create([
                'user_id' => auth()->id(),
                'target_user_id' => $employee->user_id,
                'activity' => 'Updated faculty information: ' . $validated['full_name'],
                'activity_type' => 'profile_update',
                'visibility' => 'coordinator',
            ]);

            DB::commit();
            return redirect()->route('coordinator.faculty-profile', $id)
                ->with('success', 'Faculty information updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update faculty information: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function resetFacultyPassword(Request $request, $id)
    {
        $employee = Employee::with('user')
            ->where('employee_id', $id)
            ->firstOrFail();

        // Only allow resetting password for faculty employees
        if ($employee->user->role_id !== 3) {
            abort(403, 'Unauthorized access');
        }

        $validated = $request->validate([
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $employee->user->update([
                'password' => Hash::make($validated['new_password']),
            ]);

            // Create notification for faculty
            Notification::create([
                'user_id' => $employee->user_id,
                'message' => 'Your password has been reset by ' . auth()->user()->employee->full_name . ' (Program Coordinator). Please use your new password to login.',
            ]);

            // Log activity visible to Dean, Coordinator, and affected Faculty
            DashboardLog::create([
                'user_id' => auth()->id(),
                'target_user_id' => $employee->user_id,
                'activity' => 'Reset password for faculty: ' . $employee->full_name,
                'activity_type' => 'password_reset',
                'visibility' => 'coordinator',
            ]);

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

        // Only allow viewing faculty employees
        if ($employee->user->role_id !== 3) {
            abort(403, 'Unauthorized access');
        }

        $performanceReports = collect(); // Coordinator cannot see performance reports

        $tasks = Task::with('assignedBy.employee')
            ->where('assigned_to', $employee->user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        $taskStats = [
            'total' => $tasks->count(),
            'completed' => $tasks->where('status', 'Completed')->count(),
            'pending' => $tasks->where('status', 'Pending')->count(),
        ];

        $documents = Document::where('uploaded_by', $employee->user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        $documentStats = [
            'total' => $documents->count(),
            'byType' => $documents->groupBy('document_type')->map->count(),
        ];

        $reports = \App\Models\Report::where('submitted_by', $employee->user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        $reportStats = [
            'total' => $reports->count(),
            'byCategory' => $reports->groupBy('report_category')->map->count(),
        ];

        return view('employees.profile', compact('employee', 'performanceReports', 'tasks', 'taskStats', 'documents', 'documentStats', 'reports', 'reportStats'));
    }

    public function viewDocument($id)
    {
        $document = Document::findOrFail($id);

        if (!$document->canView(auth()->user())) {
            abort(403, 'Unauthorized access');
        }

        if (!Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'File not found');
        }

        // Track document view
        DocumentView::trackView(auth()->id(), $id);

        $mimeType = Storage::disk('local')->mimeType($document->file_path);
        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];
        if (!in_array($mimeType, $allowedMimes)) {
            $mimeType = 'application/octet-stream';
        }

        return Storage::disk('local')->response($document->file_path, null, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($document->file_path) . '"',
        ]);
    }

    public function downloadDocument($id)
    {
        $document = Document::findOrFail($id);

        if (!$document->canView(auth()->user())) {
            abort(403, 'Unauthorized access');
        }

        if (!Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'File not found');
        }

        return Storage::disk('local')->download($document->file_path, basename($document->file_path));
    }

    // Toggle document favorite
    public function toggleFavorite($id)
    {
        $document = Document::findOrFail($id);
        $isFavorited = $document->toggleFavorite(auth()->id());
        
        $message = $isFavorited ? 'Document added to favorites' : 'Document removed from favorites';
        return response()->json(['success' => true, 'favorited' => $isFavorited, 'message' => $message]);
    }
}
