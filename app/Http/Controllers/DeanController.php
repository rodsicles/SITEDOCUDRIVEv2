<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PerformanceReport;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\DocumentService;
use App\Services\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DeanController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
        protected DocumentService $documentService,
        protected EmployeeService $employeeService
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

        return view('dean.dashboard', array_merge($stats, $docAnalyticsData, compact(
            'monthlyUsage',
            'monthNames',
            'recentActivities',
            'performanceData',
            'topPerformers',
            'announcements'
        )));
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
        return view('dean.analytics', $data);
    }

    public function documents(Request $request)
    {
        $categoryFilter = $request->query('category');
        $folderFilter = $request->query('folder');

        $folders = $this->documentService->getUserFolders(auth()->id());
        $documents = $this->documentService->getFilteredDocuments(
            auth()->user(), $categoryFilter, $folderFilter, $request->query()
        );
        $categories = $this->documentService->getCategories();

        return view('dean.documents', compact('documents', 'categories', 'categoryFilter', 'folders', 'folderFilter'));
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
