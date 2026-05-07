<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\ProfessionalDevelopment;
use App\Services\ProfessionalDevelopmentService;

class ProfessionalDevelopmentController extends Controller
{
    public function __construct(
        protected ProfessionalDevelopmentService $pdService
    ) {}

    public function index()
    {
        $user = auth()->user();

        if ($user->isFaculty()) {
            $records = $this->pdService->getForUser($user->id);
            $totalTrainings = ProfessionalDevelopment::where('user_id', $user->id)->count();
            $totalHours = ProfessionalDevelopment::where('user_id', $user->id)->sum('hours');
            return view('professional-development.index', compact('records', 'totalTrainings', 'totalHours'));
        }

        $records = $this->pdService->getAll();
        $summary = $this->pdService->getSummary();
        $totalTrainings = ProfessionalDevelopment::count();
        $totalHours = ProfessionalDevelopment::sum('hours');
        return view('professional-development.index', compact('records', 'summary', 'totalTrainings', 'totalHours'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'seminar_name' => 'required|string|max:150',
            'date_attended' => 'required|date|before_or_equal:today',
            'organizer' => 'required|string|max:150',
            'hours' => 'required|numeric|min:0.5|max:999',
            'certificate' => 'nullable|image|mimes:jpg,jpeg,png|max:5120|mimetypes:image/jpeg,image/png',
        ]);

        $this->pdService->create($validated, $request->file('certificate'), auth()->user());
        return redirect()->back()->with('success', 'Training record added successfully.');
    }

    public function update(Request $request, $id)
    {
        $pd = ProfessionalDevelopment::findOrFail($id);

        $validated = $request->validate([
            'seminar_name' => 'required|string|max:150',
            'date_attended' => 'required|date|before_or_equal:today',
            'organizer' => 'required|string|max:150',
            'hours' => 'required|numeric|min:0.5|max:999',
            'certificate' => 'nullable|image|mimes:jpg,jpeg,png|max:5120|mimetypes:image/jpeg,image/png',
        ]);

        $this->pdService->update($pd, $validated, $request->file('certificate'), auth()->id());
        return redirect()->back()->with('success', 'Training record updated.');
    }

    public function certificate($id)
    {
        $pd = ProfessionalDevelopment::findOrFail($id);

        if (!$pd->hasCertificate()) {
            abort(404, 'No certificate uploaded.');
        }

        $user = auth()->user();
        // Owner, Dean, or Coordinator may view
        if ((int) $pd->user_id !== (int) $user->id && !$user->isDean() && !$user->isSecretary() && !$user->isProgramCoordinator()) {
            abort(403);
        }

        if (!Storage::disk('local')->exists($pd->certificate_path)) {
            abort(404, 'Certificate file not found.');
        }

        $absolutePath = Storage::disk('local')->path($pd->certificate_path);

        return response()->file($absolutePath, [
            'Content-Type' => Storage::disk('local')->mimeType($pd->certificate_path),
        ]);
    }

    public function destroy($id)
    {
        $pd = ProfessionalDevelopment::findOrFail($id);
        $this->pdService->delete($pd, auth()->id());
        return redirect()->back()->with('success', 'Training record deleted.');
    }
}
