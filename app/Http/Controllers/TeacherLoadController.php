<?php

namespace App\Http\Controllers;

use App\Models\{Program, SchoolYear, TeacherLoad, TeacherLoadItem};
use App\Services\TeacherLoadService;
use App\Support\SchoolTerm;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Validation\{Rule, ValidationException};

class TeacherLoadController extends Controller
{
    public function __construct(private TeacherLoadService $service) {}

    public function index(Request $request)
    {
        $viewer = $request->user();
        $manager = $viewer->isDean() || $viewer->isProgramCoordinator();
        $filters = $request->validate([
            'program' => ['nullable', Rule::in(Program::codes())],
            'school_year_id' => ['nullable', 'integer', 'exists:school_years,id'],
            'semester' => ['nullable', Rule::in(SchoolTerm::options())],
            'employee_id' => ['nullable', 'integer'],
        ]);
        $loads = TeacherLoad::visibleTo($viewer);
        foreach (['program', 'school_year_id', 'semester', 'employee_id'] as $key) {
            if (!empty($filters[$key])) $loads->where($key, $filters[$key]);
        }
        $loads = $loads->orderByDesc('created_at')->paginate(20)->withQueryString();
        $faculty = $manager ? $this->service->facultyQuery($viewer)->orderBy('full_name')->get(['employee_id', 'full_name', 'program']) : collect();
        $years = SchoolYear::orderByDesc('start_year')->get();
        return view('teacher-loads.index', compact('loads', 'faculty', 'years', 'manager', 'filters'));
    }

    private function manager(Request $request): void
    {
        abort_unless($request->user()->isDean() || $request->user()->isProgramCoordinator(), 403);
    }

    private function find(Request $request, int $id): TeacherLoad
    {
        return TeacherLoad::visibleTo($request->user())->with('items')->findOrFail($id);
    }

    public function options(Request $request)
    {
        $this->manager($request);
        $data = $request->validate(['employee_id' => 'required|integer', 'semester' => ['required', Rule::in(SchoolTerm::options())]]);
        $faculty = $this->service->facultyQuery($request->user())->findOrFail($data['employee_id']);
        return response()->json(['courses' => $this->service->assignedCourses($faculty, $data['semester']), 'program' => $faculty->program, 'department' => 'SITE', 'employment_status' => $faculty->facultyTypeLabel()]);
    }

    public function show(Request $request, int $id)
    {
        return response()->json($this->find($request, $id))->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request)
    {
        $this->manager($request);
        try {
            $load = $this->service->save($request->user(), $request->all());
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages(['semester' => 'A load already exists for this faculty and term. Refresh the list to open it.']);
        }
        return response()->json($load, 201);
    }

    public function update(Request $request, int $id)
    {
        $this->manager($request);
        return response()->json($this->service->save($request->user(), $request->all(), $this->find($request, $id)));
    }

    public function finalize(Request $request, int $id)
    {
        $this->manager($request);
        $data = $request->validate(['lock_version' => 'required|integer|min:1']);
        return response()->json($this->service->finalize($request->user(), $this->find($request, $id), $data['lock_version']));
    }

    public function preview(Request $request)
    {
        $this->manager($request);
        $request->validate(['id' => 'nullable|integer']);
        $existing = $request->filled('id') ? $this->find($request, (int) $request->input('id')) : null;
        abort_if($existing && $existing->status !== 'draft', 409, 'Use the saved PDF for finalized records.');
        $data = $this->service->validate($request->user(), $request->all(), $existing);
        $load = new TeacherLoad($data['header'] + ['status' => 'draft']);
        $load->setRelation('items', collect($data['items'])->map(fn ($row) => new TeacherLoadItem($row)));
        return $this->pdf($load, true);
    }

    public function export(Request $request, int $id)
    {
        return $this->pdf($this->find($request, $id), $request->boolean('inline'));
    }

    private function pdf(TeacherLoad $load, bool $inline)
    {
        $logo = 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/SPUP-final-logo.png')));
        $pdf = Pdf::loadView('teacher-loads.pdf', compact('load', 'logo'))->setPaper('a4', 'portrait')
            ->setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false, 'defaultFont' => 'DejaVu Serif']);
        $pdf->render();
        $pdf->getDomPDF()->getCanvas()->page_text(478, 814, 'Page {PAGE_NUM} of {PAGE_COUNT}', $pdf->getDomPDF()->getFontMetrics()->getFont('DejaVu Serif'), 8);
        $filename = 'teacher-load-'.($load->id ?? 'preview').'.pdf';
        $response = $inline ? $pdf->stream($filename) : $pdf->download($filename);
        return $response->header('Cache-Control', 'private, no-store');
    }
}
