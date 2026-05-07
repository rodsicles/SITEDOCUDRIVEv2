<?php

namespace App\Http\Controllers;

use App\Models\ExamQuestionnaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExamQuestionnaireController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $search = $request->query('search');
        $statusFilter = $request->query('status');
        $examTypeFilter = $request->query('exam_type');
        $semesterFilter = $request->query('semester');

        $query = ExamQuestionnaire::with('submitter.employee', 'reviewer.employee')
            ->latest();

        // Faculty sees only their own submissions
        if ($user->isFaculty()) {
            $query->where('submitted_by', $user->id);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        if ($examTypeFilter) {
            $query->where('exam_type', $examTypeFilter);
        }

        if ($semesterFilter) {
            $query->where('semester', $semesterFilter);
        }

        $questionnaires = $query->paginate(15);

        $role = $this->getViewRole($user);
        return view("{$role}.exam-questionnaires", compact(
            'questionnaires', 'search', 'statusFilter', 'examTypeFilter', 'semesterFilter'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject'   => 'required|string|max:100',
            'exam_type' => 'required|in:Quiz,Prelim,Midterm,Pre-Final,Final',
            'file'      => 'required|file|max:10240|mimes:pdf|mimetypes:application/pdf',
        ]);

        $file = $request->file('file');

        // Block double-extension attacks
        $originalName = $file->getClientOriginalName();
        if (preg_match('/\.(php|phtml|exe|sh|bat|cmd|com|vbs|js|jsp|asp|aspx)(\.|$)/i', $originalName)) {
            return back()->with('error', 'Invalid file type.');
        }

        $quotaService = app(\App\Services\StorageQuotaService::class);
        if (!$quotaService->hasQuotaForBytes(auth()->id(), (int) ($file->getSize() ?? 0))) {
            return back()->with('error', 'Storage quota exceeded (limit: ' . $quotaService->formatBytes(\App\Services\StorageQuotaService::DEFAULT_QUOTA_BYTES) . ').');
        }

        // Auto-detect semester and academic year from current date
        $month = now()->month;
        $year  = now()->year;
        if ($month >= 8) {
            $semester     = '1st';
            $academicYear = $year . '-' . ($year + 1);
        } else {
            $semester     = '2nd';
            $academicYear = ($year - 1) . '-' . $year;
        }

        // Auto-generate title
        $title = $validated['subject'] . ' - ' . $validated['exam_type'] . ' Questionnaire';

        $extension = strtolower($file->getClientOriginalExtension());
        $fileType  = $extension === 'pdf' ? 'pdf' : 'word';
        $filename  = time() . '_' . $file->hashName();
        $file->storeAs('exam-questionnaires', $filename, 'local');

        ExamQuestionnaire::create([
            'submitted_by'  => auth()->id(),
            'title'         => $title,
            'file_path'     => 'exam-questionnaires/' . $filename,
            'file_type'     => $fileType,
            'subject'       => $validated['subject'],
            'exam_type'     => $validated['exam_type'],
            'semester'      => $semester,
            'academic_year' => $academicYear,
            'status'        => 'pending',
        ]);

        return back()->with('success', 'Exam questionnaire submitted successfully.');
    }

    public function view($id)
    {
        $user = auth()->user();
        $questionnaire = ExamQuestionnaire::findOrFail($id);

        // Faculty can only view their own
        if ($user->isFaculty() && (int)$questionnaire->submitted_by !== (int)$user->id) {
            abort(403);
        }

        // Path traversal protection
        if (str_contains($questionnaire->file_path, '..') || str_contains($questionnaire->file_path, './')) {
            abort(403, 'Invalid file path');
        }

        if (!Storage::disk('local')->exists($questionnaire->file_path)) {
            abort(404, 'File not found.');
        }

        $path = Storage::disk('local')->path($questionnaire->file_path);

        return response()->file($path, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $questionnaire->title . '.pdf"',
        ]);
    }

    public function download($id)
    {
        $user = auth()->user();
        $questionnaire = ExamQuestionnaire::findOrFail($id);

        // Faculty can only download their own
        if ($user->isFaculty() && (int)$questionnaire->submitted_by !== (int)$user->id) {
            abort(403);
        }

        // Path traversal protection
        if (str_contains($questionnaire->file_path, '..') || str_contains($questionnaire->file_path, './')) {
            abort(403, 'Invalid file path');
        }

        if (!Storage::disk('local')->exists($questionnaire->file_path)) {
            abort(404, 'File not found.');
        }

        $path = Storage::disk('local')->path($questionnaire->file_path);

        return response()->download($path, $questionnaire->title . '.pdf');
    }

    public function approve(Request $request, $id)
    {
        $user = auth()->user();
        if ($user->isFaculty()) abort(403);

        $questionnaire = ExamQuestionnaire::findOrFail($id);
        $questionnaire->update([
            'status'      => 'approved',
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'remarks'     => $request->input('remarks'),
        ]);

        return back()->with('success', 'Questionnaire approved.');
    }

    public function reject(Request $request, $id)
    {
        $user = auth()->user();
        if ($user->isFaculty()) abort(403);

        $request->validate(['remarks' => 'required|string|max:500']);

        $questionnaire = ExamQuestionnaire::findOrFail($id);
        $questionnaire->update([
            'status'      => 'rejected',
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'remarks'     => $request->input('remarks'),
        ]);

        return back()->with('success', 'Questionnaire rejected.');
    }

    public function destroy($id)
    {
        $user = auth()->user();
        $questionnaire = ExamQuestionnaire::findOrFail($id);

        // Faculty can only delete their own pending submissions
        if ($user->isFaculty()) {
            if ((int)$questionnaire->submitted_by !== (int)$user->id || !$questionnaire->isPending()) {
                abort(403);
            }
        }

        Storage::disk('local')->delete($questionnaire->file_path);
        $questionnaire->delete();

        return back()->with('success', 'Questionnaire deleted.');
    }

    private function getViewRole($user): string
    {
        if ($user->isDean() || $user->isSecretary()) return 'dean';
        if ($user->isProgramCoordinator()) return 'coordinator';
        return 'faculty';
    }
}
