<?php

namespace App\Http\Controllers;

use App\Models\DocumentSearchIndex;
use App\Models\SavedDocumentSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DocumentSearchController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'employee_id' => ['nullable', 'integer'],
            'program' => ['nullable', 'string', 'max:100'],
            'course_id' => ['nullable', 'integer'],
            'school_year_id' => ['nullable', 'integer'],
            'semester' => ['nullable', 'string', 'max:30'],
            'status' => ['nullable', 'string', 'max:30'],
            'type' => ['nullable', 'string', 'max:20'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'saved' => ['nullable', 'integer'],
        ]);

        if (!empty($filters['saved'])) {
            $saved = SavedDocumentSearch::where('user_id', $user->id)->findOrFail($filters['saved']);
            $filters = array_merge($saved->filters, ['saved' => $saved->id]);
        }

        $route = $user->isDean() || $user->isSecretary()
            ? 'dean.documents'
            : ($user->isProgramCoordinator() ? 'coordinator.documents' : 'faculty.documents');

        return redirect()->route($route, array_filter([
            'search' => $filters['q'] ?? null,
            'uploaded_by' => $filters['employee_id'] ?? null,
            'program' => $filters['program'] ?? null,
            'course_id' => $filters['course_id'] ?? null,
            'school_year_id' => $filters['school_year_id'] ?? null,
            'semester' => $filters['semester'] ?? null,
            'status' => $filters['status'] ?? null,
            'file_type' => ($filters['type'] ?? null) === 'doc' ? 'word' : ($filters['type'] ?? null),
            'date_from' => $filters['date_from'] ?? null,
            'date_to' => $filters['date_to'] ?? null,
            'scope' => 'all',
        ], fn ($value) => $value !== null && $value !== ''));

    }

    public function save(Request $request)
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:80'], 'filters' => ['required', 'array']]);
        SavedDocumentSearch::updateOrCreate(
            ['user_id' => $request->user()->id, 'name' => $validated['name']],
            ['filters' => collect($validated['filters'])->only(['q','employee_id','program','course_id','school_year_id','semester','status','type','date_from','date_to'])->filter(fn ($value) => $value !== null && $value !== '')->all()]
        );
        return back()->with('success', 'Search saved.');
    }

    public function destroy(Request $request, SavedDocumentSearch $savedSearch)
    {
        abort_unless((int) $savedSearch->user_id === (int) $request->user()->id, 403);
        $savedSearch->delete();
        return back()->with('success', 'Saved search removed.');
    }

    public function duplicate(Request $request)
    {
        $validated = $request->validate(['hash' => ['required', 'string', 'size:64']]);
        $match = DocumentSearchIndex::where('file_hash', $validated['hash'])
            ->whereHas('document', fn ($q) => $q->visibleTo($request->user()))
            ->with('document:id,document_title')
            ->first();
        return response()->json(['duplicate' => (bool) $match, 'document' => $match?->document?->document_title]);
    }

    private function excerpt(string $content, string $term): string
    {
        if ($content === '' || $term === '') return '';
        $position = mb_stripos($content, $term);
        $start = $position === false ? 0 : max(0, $position - 110);
        return ($start > 0 ? '…' : '').Str::limit(mb_substr($content, $start, 280), 280);
    }
}
