<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PerformanceReport;
use App\Services\DashboardService;
use App\Services\DocumentService;
use App\Services\EmployeeService;
use Illuminate\Http\Request;

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
}
