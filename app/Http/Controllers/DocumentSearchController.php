<?php

namespace App\Http\Controllers;

use App\Jobs\IndexDocumentContentJob;
use App\Models\Document;
use App\Models\DocumentSearchIndex;
use App\Models\SavedDocumentSearch;
use App\Services\DocumentSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DocumentSearchController extends Controller
{
    public function __construct(
        protected DocumentSearchService $documentSearch,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'scope' => ['nullable', 'string', 'max:20'],
            'employee_id' => ['nullable', 'integer'],
            'program' => ['nullable', 'string', 'max:12'],
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
            $filters = array_merge(
                $this->documentSearch->normalizeSavedFilters($user, $saved->filters ?? []),
                ['saved' => $saved->id]
            );
        } else {
            $filters = $this->documentSearch->normalizeFilters($user, $filters);
        }

        $filters['scope'] = $filters['scope'] ?? DocumentSearchService::SCOPE_ALL;

        $documents = $this->documentSearch->paginate($user, $filters, null, 20);
        $options = $this->documentSearch->filterOptions($user);
        $employees = $this->documentSearch->filterOptionsEmployees($user);
        $savedSearches = SavedDocumentSearch::where('user_id', $user->id)->orderBy('name')->get();
        $migrationReady = Schema::hasTable('employee_programs');

        return view('document-search.index', [
            'documents' => $documents,
            'filters' => $filters,
            'employees' => $employees,
            'courses' => $options['courses'],
            'schoolYears' => $options['schoolYears'],
            'archivedSchoolYears' => $options['archivedSchoolYears'],
            'savedSearches' => $savedSearches,
            'programOptions' => $options['programs'],
            'migrationReady' => $migrationReady,
        ]);
    }

    public function save(Request $request)
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:80'], 'filters' => ['required', 'array']]);
        $filters = $this->documentSearch->normalizeSavedFilters(
            $request->user(),
            collect($validated['filters'])->only(['q', 'employee_id', 'program', 'course_id', 'school_year_id', 'semester', 'status', 'type', 'date_from', 'date_to', 'scope'])->filter(fn ($value) => $value !== null && $value !== '')->all()
        );

        SavedDocumentSearch::updateOrCreate(
            ['user_id' => $request->user()->id, 'name' => $validated['name']],
            ['filters' => $filters]
        );

        return back()->with('success', 'Search saved.');
    }

    public function destroy(Request $request, SavedDocumentSearch $savedSearch)
    {
        abort_unless((int) $savedSearch->user_id === (int) $request->user()->id, 403);
        $savedSearch->delete();

        return back()->with('success', 'Saved search removed.');
    }

    public function reindex(Request $request, int $documentId)
    {
        $document = Document::query()->visibleTo($request->user())->findOrFail($documentId);
        abort_unless($document->canView($request->user()), 403);

        DocumentSearchIndex::updateOrCreate(
            ['document_id' => $document->document_id],
            ['index_status' => 'pending', 'index_error' => null]
        );
        IndexDocumentContentJob::dispatch($document->document_id);

        return back()->with('success', 'Content indexing has been queued again for this document.');
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
}
