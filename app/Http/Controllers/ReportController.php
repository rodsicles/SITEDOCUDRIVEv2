<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    use \App\Http\Controllers\Concerns\HandlesUploadExceptions;

    public function __construct(
        protected ReportService $reportService
    ) {}

    public function index()
    {
        $reports = $this->reportService->listForUser(auth()->id());
        $categories = Report::CATEGORIES;
        $openSubmit = request()->boolean('submit');

        return view('faculty.reports', compact('reports', 'categories', 'openSubmit'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'report_title' => 'required|string|max:200',
            'report_category' => ['nullable', 'string', Rule::in(Report::CATEGORIES)],
            'description' => 'nullable|string|max:2000',
            'report_file' => 'required|file|max:10240|mimes:pdf,doc,docx|mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);

        $file = $request->file('report_file');
        $originalName = $file->getClientOriginalName();
        if (preg_match('/\.(php|phtml|phar|exe|sh|bat|cmd|com|cgi|pl|py|jsp|asp|aspx|htaccess)/i', pathinfo($originalName, PATHINFO_FILENAME))) {
            return back()->with('error', 'File contains a forbidden extension in its name.')->withInput();
        }

        try {
            $this->reportService->create($validated, $file, auth()->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        } catch (\Throwable $e) {
            return $this->uploadFailedResponse($request, $e);
        }

        return redirect()->route('faculty.reports')->with('success', 'Report submitted successfully.');
    }

    public function view(int $id)
    {
        $report = Report::findOrFail($id);
        $this->authorizeReport($report);

        return $this->reportService->serve($report, false);
    }

    public function download(int $id)
    {
        $report = Report::findOrFail($id);
        $this->authorizeReport($report);

        return $this->reportService->serve($report, true);
    }

    protected function authorizeReport(Report $report): void
    {
        if (!$report->canBeAccessedBy(auth()->user())) {
            abort(403);
        }
    }
}
