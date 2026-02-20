<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employee;
use App\Models\Task;
use App\Models\PerformanceReport;
use App\Models\Document;
use App\Models\DashboardLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeanController extends Controller
{
    public function dashboard()
    {
        $totalEmployees = Employee::count();
        $totalTasks = Task::count();
        $completedTasks = Task::where('status', 'Completed')->count();
        $pendingTasks = Task::where('status', 'Pending')->count();
        
        // Dean sees all activities using filtered logs
        $recentActivities = DashboardLog::getFilteredLogs(auth()->user(), 10);
        
        $performanceData = PerformanceReport::select(
                DB::raw('AVG(rating) as avg_rating'),
                DB::raw('MONTH(report_date) as month')
            )
            ->whereYear('report_date', date('Y'))
            ->groupBy('month')
            ->get();

        $topPerformers = PerformanceReport::with('employee')
            ->select('employee_id', DB::raw('AVG(rating) as avg_rating'))
            ->groupBy('employee_id')
            ->orderByDesc('avg_rating')
            ->take(5)
            ->get();

        return view('dean.dashboard', compact(
            'totalEmployees',
            'totalTasks',
            'completedTasks',
            'pendingTasks',
            'recentActivities',
            'performanceData',
            'topPerformers'
        ));
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
        $taskStatusData = Task::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        $departmentData = Employee::select('department', DB::raw('count(*) as count'))
            ->whereNotNull('department')
            ->groupBy('department')
            ->get();

        $monthlyPerformance = PerformanceReport::select(
                DB::raw('DATE_FORMAT(report_date, "%Y-%m") as month'),
                DB::raw('AVG(rating) as avg_rating'),
                DB::raw('COUNT(*) as total_reports')
            )
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->take(12)
            ->get();

        return view('dean.analytics', compact(
            'taskStatusData',
            'departmentData',
            'monthlyPerformance'
        ));
    }

    public function documents()
    {
        $documents = Document::getFilteredDocuments(auth()->user())->paginate(15);
        return view('dean.documents', compact('documents'));
    }

    public function viewEmployeeProfile($id)
    {
        $employee = Employee::with(['user.role', 'performanceReports.evaluator.employee'])
            ->where('employee_id', $id)
            ->firstOrFail();

        $performanceReports = PerformanceReport::with('evaluator.employee')
            ->where('employee_id', $id)
            ->orderBy('report_date', 'desc')
            ->get();

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
}
