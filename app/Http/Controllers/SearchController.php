<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Employee;
use App\Models\Task;
use App\Models\Document;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $results = [];
        $user = auth()->user();

        // Search Employees (Dean and Coordinator only)
        if ($user->isDean() || $user->isProgramCoordinator()) {
            $employees = Employee::where('full_name', 'LIKE', "%{$query}%")
                ->orWhere('employee_no', 'LIKE', "%{$query}%")
                ->orWhere('department', 'LIKE', "%{$query}%")
                ->limit(5)
                ->get();

            foreach ($employees as $employee) {
                $results[] = [
                    'title' => $employee->full_name,
                    'type' => 'Employee - ' . ($employee->department ?? 'N/A'),
                    'url' => $user->isDean() ? route('dean.employees') : route('coordinator.faculty'),
                ];
            }
        }

        // Search Tasks
        $tasksQuery = Task::where('task_title', 'LIKE', "%{$query}%")
            ->orWhere('task_description', 'LIKE', "%{$query}%");

        if ($user->isFaculty()) {
            $tasksQuery->where('assigned_to', $user->id);
        } elseif ($user->isProgramCoordinator()) {
            $tasksQuery->where('assigned_by', $user->id);
        }

        $tasks = $tasksQuery->limit(5)->get();

        foreach ($tasks as $task) {
            $url = match(true) {
                $user->isDean() => route('dean.dashboard'),
                $user->isProgramCoordinator() => route('coordinator.tasks'),
                $user->isFaculty() => route('faculty.tasks'),
                default => '#',
            };

            $results[] = [
                'title' => $task->task_title,
                'type' => 'Task - ' . $task->status,
                'url' => $url,
            ];
        }

        // Search Documents
        $documents = Document::where('document_title', 'LIKE', "%{$query}%")
            ->orWhere('document_type', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get();

        foreach ($documents as $document) {
            $url = match(true) {
                $user->isDean() => route('dean.documents'),
                $user->isProgramCoordinator() => route('coordinator.documents'),
                $user->isFaculty() => route('faculty.documents'),
                default => '#',
            };

            $results[] = [
                'title' => $document->document_title,
                'type' => 'Document - ' . ($document->document_type ?? 'General'),
                'url' => $url,
            ];
        }

        return response()->json($results);
    }
}
