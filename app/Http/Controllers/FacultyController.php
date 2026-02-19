<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Notification;
use App\Models\Document;
use App\Models\DocumentView;
use App\Models\PerformanceReport;
use Illuminate\Http\Request;

class FacultyController extends Controller
{
    public function dashboard()
    {
        $totalTasks = Task::where('assigned_to', auth()->id())->count();
        $pendingTasks = Task::where('assigned_to', auth()->id())
            ->where('status', 'Pending')
            ->count();
        $inProgressTasks = Task::where('assigned_to', auth()->id())
            ->where('status', 'In Progress')
            ->count();
        $completedTasks = Task::where('assigned_to', auth()->id())
            ->where('status', 'Completed')
            ->count();

        $recentTasks = Task::with('assignedBy')
            ->where('assigned_to', auth()->id())
            ->latest()
            ->take(5)
            ->get();

        $unreadNotifications = Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->count();

        $recentNotifications = Notification::where('user_id', auth()->id())
            ->latest()
            ->take(5)
            ->get();

        $employee = auth()->user()->employee;
        $performanceReports = PerformanceReport::with('evaluator')
            ->where('employee_id', $employee->employee_id)
            ->latest('report_date')
            ->take(5)
            ->get();

        // Faculty sees only their own activities and notifications
        $recentActivities = \App\Models\DashboardLog::getFilteredLogs(auth()->user(), 10);

        return view('faculty.dashboard', compact(
            'totalTasks',
            'pendingTasks',
            'inProgressTasks',
            'completedTasks',
            'recentTasks',
            'unreadNotifications',
            'recentNotifications',
            'performanceReports',
            'recentActivities'
        ));
    }

    public function tasks()
    {
        $tasks = Task::with('assignedBy')
            ->where('assigned_to', auth()->id())
            ->paginate(15);
        return view('faculty.tasks', compact('tasks'));
    }

    public function updateTaskStatus(Request $request, $id)
    {
        $task = Task::where('task_id', $id)
            ->where('assigned_to', auth()->id())
            ->firstOrFail();

        $validated = $request->validate([
            'status' => 'required|in:Pending,In Progress,Completed',
        ]);

        $task->update($validated);

        Notification::create([
            'user_id' => $task->assigned_by,
            'message' => 'Task "' . $task->task_title . '" status updated to: ' . $validated['status'],
        ]);

        // Log task status update
        \App\Models\DashboardLog::create([
            'user_id' => auth()->id(),
            'target_user_id' => $task->assigned_by,
            'activity' => 'Updated task status: "' . $task->task_title . '" to ' . $validated['status'],
            'activity_type' => 'task_update',
            'visibility' => 'own',
        ]);

        return redirect()->back()->with('success', 'Task status updated successfully');
    }

    public function notifications()
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->latest()
            ->paginate(20);
        return view('faculty.notifications', compact('notifications'));
    }

    public function markNotificationRead($id)
    {
        $notification = Notification::where('notification_id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $notification->update(['is_read' => true]);

        return redirect()->back();
    }

    public function documents()
    {
        $documents = Document::getFilteredDocuments(auth()->user())->paginate(15);
        $recentDocuments = DocumentView::getRecentDocuments(auth()->id(), 5);
        $favoriteDocuments = auth()->user()->documentFavorites()->with('document')->get()->pluck('document');
        
        return view('faculty.documents', compact('documents', 'recentDocuments', 'favoriteDocuments'));
    }

    public function uploadDocument(Request $request)
    {
        $validated = $request->validate([
            'document_title' => 'required|string|max:150',
            'document_type' => 'required|in:pdf,image',
            'documents' => 'required|array',
            'documents.*' => 'required|file|max:10240',
            'category' => 'required|in:Policies,Forms,Reports,Memos,Research Papers,Other',
            'tags' => 'nullable|string',
        ]);

        // Additional validation: Ensure file extensions match document_type
        $documentType = $validated['document_type'];
        foreach ($request->file('documents') as $file) {
            $extension = strtolower($file->getClientOriginalExtension());
            
            if ($documentType === 'pdf' && $extension !== 'pdf') {
                return redirect()->back()
                    ->withErrors(['documents' => 'You selected "PDF Document" but uploaded a non-PDF file.'])
                    ->withInput();
            }
            
            if ($documentType === 'image' && !in_array($extension, ['jpg', 'jpeg', 'png'])) {
                return redirect()->back()
                    ->withErrors(['documents' => 'You selected "Image File" but uploaded an invalid image format. Only JPG, JPEG, PNG allowed.'])
                    ->withInput();
            }
        }

        // Parse tags
        $tags = !empty($validated['tags']) ? implode(',', array_map('trim', explode(',', $validated['tags']))) : '';

        $uploadedCount = 0;
        foreach ($request->file('documents') as $index => $file) {
            $filename = time() . '_' . $index . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/documents'), $filename);

            Document::create([
                'uploaded_by' => auth()->id(),
                'document_title' => $validated['document_title'] . ($uploadedCount > 0 ? ' (' . ($uploadedCount + 1) . ')' : ''),
                'file_path' => 'uploads/documents/' . $filename,
                'document_type' => $validated['document_type'],
                'category' => $validated['category'],
                'tags' => $tags,
            ]);
            $uploadedCount++;
        }

        // Log document upload activity (visible to Faculty, Coordinator, Dean)
        \App\Models\DashboardLog::create([
            'user_id' => auth()->id(),
            'activity' => 'Uploaded ' . $uploadedCount . ' document(s): ' . $validated['document_title'],
            'activity_type' => 'document_upload',
            'visibility' => 'own',
        ]);

        return redirect()->back()->with('success', "$uploadedCount document(s) uploaded successfully");
    }

    public function viewDocument($id)
    {
        $document = Document::findOrFail($id);
        $filePath = public_path($document->file_path);

        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }

        // Track document view
        DocumentView::trackView(auth()->id(), $id);

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
        ];

        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($filePath) . '"'
        ]);
    }

    public function downloadDocument($id)
    {
        $document = Document::findOrFail($id);
        $filePath = public_path($document->file_path);

        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }

        return response()->download($filePath, basename($document->file_path));
    }

    public function profile()
    {
        $employee = auth()->user()->employee;
        $performanceReports = PerformanceReport::with('evaluator')
            ->where('employee_id', $employee->employee_id)
            ->latest('report_date')
            ->get();

        return view('faculty.profile', compact('employee', 'performanceReports'));
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
