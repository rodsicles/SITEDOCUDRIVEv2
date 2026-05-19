<?php

namespace App\Http\Controllers;

use App\Models\ExamQuestionnaire;
use App\Models\SchoolYear;
use App\Services\AcademicHierarchyService;
use App\Services\ExamQuestionnaireSyncService;
use App\Services\NotificationService;
use App\Support\AcademicYear;
use App\Support\IteSubjects;
use App\Support\UploadStorage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExamQuestionnaireController extends Controller
{
    use \App\Http\Controllers\Concerns\HandlesUploadExceptions;

    public function __construct(
        protected AcademicHierarchyService $hierarchy,
        protected NotificationService $notificationService,
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();
        $search = $request->query('search');
        $statusFilter = $request->query('status');
        $examTypeFilter = $request->query('exam_type');
        $semesterFilter = $request->query('semester');
        $academicYearStart = AcademicYear::startYearFromQuery($request->query('academic_year'));

        $query = ExamQuestionnaire::with('submitter.employee', 'reviewer.employee')
            ->visibleTo($user)
            ->latest();

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

        if ($academicYearStart) {
            $query->where('academic_year', AcademicYear::rangeString($academicYearStart));
        } else {
            $activeId = SchoolYear::activeId();
            $query->where(function ($q) use ($activeId) {
                $q->where('school_year_id', $activeId)
                  ->orWhereNull('school_year_id');
            });
        }

        $questionnaires = $query->paginate(15)->appends($request->query());

        $activeId = $activeId ?? SchoolYear::activeId();
        $pendingScope = fn ($q) => $q->where('status', 'pending')
            ->where(function ($q2) use ($activeId) {
                $q2->where('school_year_id', $activeId)->orWhereNull('school_year_id');
            });
        $pendingCount = match (true) {
            $user->isDean(), $user->isSecretary() => ExamQuestionnaire::where($pendingScope)->count(),
            $user->isProgramCoordinator() => ExamQuestionnaire::visibleTo($user)->where($pendingScope)->count(),
            default => 0,
        };
        $archiveYears = array_filter(
            AcademicYear::availableStartYears(),
            fn ($y) => AcademicYear::isArchived($y)
        );

        $role = $this->getViewRole($user);

        return view("{$role}.exam-questionnaires", compact(
            'questionnaires', 'search', 'statusFilter', 'examTypeFilter',
            'semesterFilter', 'academicYearStart', 'archiveYears', 'pendingCount'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', Rule::in(IteSubjects::labels())],
            'exam_type' => 'required|in:Quiz,Prelim,Midterm,Pre-Final,Final',
            'submission_type' => 'required|in:tos,toq',
            'academic_year_start' => 'nullable|integer',
            'semester' => 'nullable|in:1st,2nd',
            'file' => 'required|file|max:10240|mimes:pdf|mimetypes:application/pdf',
        ]);

        $startYear = (int) ($validated['academic_year_start'] ?? AcademicYear::currentStartYear());
        $semester = $validated['semester'] ?? AcademicYear::currentSemester();
        $academicYear = AcademicYear::rangeString($startYear);

        $file = $request->file('file');

        if (preg_match('/\.(php|phtml|exe|sh|bat|cmd|com|vbs|js|jsp|asp|aspx)(\.|$)/i', $file->getClientOriginalName())) {
            return back()->with('error', 'Invalid file type.');
        }

        $quotaService = app(\App\Services\StorageQuotaService::class);
        if (!$quotaService->hasQuotaForBytes(auth()->id(), (int) ($file->getSize() ?? 0))) {
            return back()->with('error', 'Storage quota exceeded (limit: ' . $quotaService->formatBytes(\App\Services\StorageQuotaService::DEFAULT_QUOTA_BYTES) . ').');
        }

        $title = $validated['subject'] . ' - ' . $validated['exam_type'] . ' Questionnaire';

        try {
            $extension = strtolower($file->getClientOriginalExtension());
            $fileType = $extension === 'pdf' ? 'pdf' : 'word';
            $filename = time() . '_' . $file->hashName();
            $storedPath = UploadStorage::storeAs($file, 'exam-questionnaires', $filename);

            ExamQuestionnaire::create([
                'submitted_by' => auth()->id(),
                'title' => $title,
                'file_path' => $storedPath,
                'file_type' => $fileType,
                'subject' => $validated['subject'],
                'exam_type' => $validated['exam_type'],
                'submission_type' => $validated['submission_type'],
                'school_year_id' => SchoolYear::activeId(),
                'semester' => $semester,
                'academic_year' => $academicYear,
                'status' => 'pending',
            ]);
        } catch (\Throwable $e) {
            return $this->uploadFailedResponse($request, $e);
        }

        app(\App\Services\NotificationService::class)->notifySupervisors(
            'Exam questionnaire pending approval: ' . $title . '.'
        );

        return back()->with('success', 'Exam questionnaire submitted for Dean approval.');
    }

    public function view($id)
    {
        $user = auth()->user();
        $questionnaire = ExamQuestionnaire::visibleTo($user)->findOrFail($id);

        UploadStorage::assertPathAllowed($questionnaire->file_path);

        if (!UploadStorage::exists($questionnaire->file_path)) {
            return back()->with('error', 'This file is no longer available. It was uploaded to a previous storage provider and no longer exists in the current storage.');
        }

        return UploadStorage::inlineResponse(
            $questionnaire->file_path,
            $questionnaire->title . '.pdf',
            'application/pdf'
        );
    }

    public function download($id)
    {
        $user = auth()->user();
        $questionnaire = ExamQuestionnaire::visibleTo($user)->findOrFail($id);

        UploadStorage::assertPathAllowed($questionnaire->file_path);

        if (!UploadStorage::exists($questionnaire->file_path)) {
            return back()->with('error', 'This file is no longer available. It was uploaded to a previous storage provider and no longer exists in the current storage.');
        }

        return UploadStorage::downloadResponse($questionnaire->file_path, $questionnaire->title . '.pdf');
    }

    public function approve(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user->isDean() && !$user->isSecretary()) {
            abort(403, 'Only the Dean or Secretary can approve submissions.');
        }

        $questionnaire = ExamQuestionnaire::findOrFail($id);

        if (!$questionnaire->isPending()) {
            if ($questionnaire->isApproved() && !$questionnaire->document_id) {
                $sync = app(ExamQuestionnaireSyncService::class);
                $document = $sync->syncToDocument($questionnaire->fresh());
                if ($document) {
                    return back()->with('success', 'Approved file synced to Documents folder.');
                }

                return back()->with('warning', 'Submission is approved but could not be synced. Ensure TOS/TOQ folders exist for the active school year.');
            }

            return back()->with('info', 'This submission has already been reviewed.');
        }

        $questionnaire->update([
            'status' => 'approved',
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'remarks' => $request->input('remarks'),
        ]);

        $fresh = $questionnaire->fresh();
        $sync = app(ExamQuestionnaireSyncService::class);
        $document = $sync->syncToDocument($fresh);

        $this->notificationService->notifyExamQuestionnaireApproved($fresh, $user);

        if (!$document) {
            return back()->with('warning', 'Questionnaire approved, but it could not be placed in the Documents folder. Ensure TOS/TOQ folders exist for the active school year.');
        }

        return back()->with('success', 'Questionnaire approved and added to Documents.');
    }

    public function reject(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user->isDean() && !$user->isSecretary()) {
            abort(403, 'Only the Dean or Secretary can reject submissions.');
        }

        $request->validate(['remarks' => 'required|string|max:500']);

        $questionnaire = ExamQuestionnaire::findOrFail($id);

        if (!$questionnaire->isPending()) {
            return back()->with('info', 'This submission has already been reviewed.');
        }

        $questionnaire->update([
            'status' => 'rejected',
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'remarks' => $request->input('remarks'),
        ]);

        $this->notificationService->notifyExamQuestionnaireRejected($questionnaire->fresh(), $user);

        return back()->with('success', 'Questionnaire rejected.');
    }

    public function destroy($id)
    {
        $user = auth()->user();
        $questionnaire = ExamQuestionnaire::visibleTo($user)->findOrFail($id);

        if ($user->isFaculty() && !$questionnaire->isPending()) {
            abort(403);
        }

        UploadStorage::delete($questionnaire->file_path);
        $questionnaire->delete();

        return back()->with('success', 'Questionnaire deleted.');
    }

    private function getViewRole($user): string
    {
        if ($user->isDean() || $user->isSecretary()) {
            return 'dean';
        }
        if ($user->isProgramCoordinator()) {
            return 'coordinator';
        }

        return 'faculty';
    }
}
