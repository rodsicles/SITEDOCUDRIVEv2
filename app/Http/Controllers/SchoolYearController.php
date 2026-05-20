<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\ExamQuestionnaire;
use App\Models\SchoolYear;
use App\Models\TeachingGuide;
use App\Services\SchoolYearService;
use App\Support\AcademicYear;
use Illuminate\Http\Request;

class SchoolYearController extends Controller
{
    public function __construct(
        protected SchoolYearService $schoolYearService,
    ) {}

    /**
     * Show the archive management page (Dean only).
     */
    public function index()
    {
        $activeSchoolYear = $this->schoolYearService->getActive();
        $archivedYears = $this->schoolYearService->getArchived();

        // Counts for active year
        $activeDocCount = Document::where(function ($q) use ($activeSchoolYear) {
            $q->where('school_year_id', $activeSchoolYear->id)
              ->orWhereNull('school_year_id');
        })->count();
        $activeTgCount = TeachingGuide::where(function ($q) use ($activeSchoolYear) {
            $q->where('school_year_id', $activeSchoolYear->id)
              ->orWhereNull('school_year_id');
        })->count();
        $activeEqCount = ExamQuestionnaire::where(function ($q) use ($activeSchoolYear) {
            $q->where('school_year_id', $activeSchoolYear->id)
              ->orWhereNull('school_year_id');
        })->count();

        // Suggest next school year
        $suggestedStartYear = $activeSchoolYear->start_year + 1;

        return view('dean.archives', compact(
            'activeSchoolYear', 'archivedYears',
            'activeDocCount', 'activeTgCount', 'activeEqCount',
            'suggestedStartYear'
        ));
    }

    /**
     * Execute the archive action (Dean only).
     */
    public function archive(Request $request)
    {
        $request->validate([
            'archive_name' => 'required|string|max:50',
            'new_name' => 'required|string|max:50',
            'new_start_year' => 'required|integer|min:2020|max:2099',
        ]);

        $user = auth()->user();

        // Prevent archiving if a year with that start_year already exists
        if (SchoolYear::where('start_year', $request->new_start_year)->exists()) {
            return back()->withInput()->with('error', 'A school year starting in ' . $request->new_start_year . ' already exists.');
        }

        $newSchoolYear = $this->schoolYearService->archive(
            $user,
            $request->archive_name,
            $request->new_name,
            (int) $request->new_start_year
        );

        return redirect()->route('dean.archives.index')
            ->with('success', "School year archived successfully. Now active: {$newSchoolYear->name}");
    }

    /**
     * Browse an archived school year's content (all roles).
     */
    public function show($id)
    {
        $schoolYear = SchoolYear::findOrFail($id);
        $user = auth()->user();

        if (!$schoolYear->isArchived()) {
            return redirect()->route('dean.archives.index');
        }

        $documentsQuery = Document::where('school_year_id', $schoolYear->id)
            ->with('uploader.employee', 'folder');

        if ($user->isFaculty()) {
            $documentsQuery->where('uploaded_by', $user->id);
        }

        $archiveSearch = trim((string) request('q', ''));
        if ($archiveSearch !== '') {
            $documentsQuery->where(function ($query) use ($archiveSearch) {
                $query->where('document_title', 'like', '%'.$archiveSearch.'%')
                    ->orWhere('category', 'like', '%'.$archiveSearch.'%')
                    ->orWhereHas('uploader', function ($uploaderQuery) use ($archiveSearch) {
                        $uploaderQuery->where('username', 'like', '%'.$archiveSearch.'%')
                            ->orWhereHas('employee', fn ($employeeQuery) => $employeeQuery->where('full_name', 'like', '%'.$archiveSearch.'%'));
                    });
            });
        }

        $documents = $documentsQuery->latest()->paginate(20)->withQueryString();

        $teachingGuidesQuery = TeachingGuide::where('school_year_id', $schoolYear->id)
            ->with('uploader.employee');

        if ($user->isFaculty()) {
            $teachingGuidesQuery->where('user_id', $user->id);
        }

        $teachingGuides = $teachingGuidesQuery->latest()->paginate(20);

        $examQuestionnairesQuery = ExamQuestionnaire::where('school_year_id', $schoolYear->id)
            ->with('submitter.employee');

        if ($user->isFaculty()) {
            $examQuestionnairesQuery->where('submitted_by', $user->id);
        }

        $examQuestionnaires = $examQuestionnairesQuery->latest()->paginate(20);

        $role = $this->getViewRole();

        return view('archives.show', compact(
            'schoolYear', 'documents', 'teachingGuides', 'examQuestionnaires', 'role'
        ));
    }

    /**
     * List archived years for browsing (all roles).
     */
    public function list()
    {
        $archivedYears = SchoolYear::archived()
            ->withCount(['documents', 'teachingGuides', 'examQuestionnaires'])
            ->get();

        $role = $this->getViewRole();

        return view('archives.index', compact('archivedYears', 'role'));
    }

    private function getViewRole(): string
    {
        $user = auth()->user();
        if ($user->isDean() || $user->isSecretary()) return 'dean';
        if ($user->isProgramCoordinator()) return 'coordinator';
        return 'faculty';
    }
}
