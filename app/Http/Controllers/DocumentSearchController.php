<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Document;
use App\Models\DocumentSearchIndex;
use App\Models\SavedDocumentSearch;
use App\Models\SchoolYear;
use App\Models\User;
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
            'department' => ['nullable', 'string', 'max:100'],
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

        $query = Document::query()->visibleTo($user)->with(['uploader.employee', 'folder', 'schoolYear', 'searchIndex']);
        $term = trim((string) ($filters['q'] ?? ''));
        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('document_title', 'like', "%{$term}%")
                    ->orWhere('subject', 'like', "%{$term}%")
                    ->orWhere('tags', 'like', "%{$term}%")
                    ->orWhereHas('searchIndex', fn ($index) => $index->where('content_text', 'like', "%{$term}%"));
            });
        }
        if (!empty($filters['employee_id'])) $query->where('uploaded_by', $filters['employee_id']);
        if (!empty($filters['department'])) $query->whereHas('uploader.employee', fn ($q) => $q->where('department', $filters['department']));
        if (!empty($filters['school_year_id'])) $query->where('school_year_id', $filters['school_year_id']);
        if (!empty($filters['semester'])) $query->whereHas('folder', fn ($q) => $q->where('folder_name', 'like', $filters['semester'].'%'));
        if (!empty($filters['type'])) {
            if ($filters['type'] === 'image') {
                $query->whereIn('document_type', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'image']);
            } elseif ($filters['type'] === 'doc') {
                $query->where(function ($q) {
                    $q->where('document_type', 'like', '%doc%')->orWhere('document_type', 'like', '%word%');
                });
            } else {
                $query->where('document_type', 'like', '%'.$filters['type'].'%');
            }
        }
        if (!empty($filters['date_from'])) $query->whereDate('created_at', '>=', $filters['date_from']);
        if (!empty($filters['date_to'])) $query->whereDate('created_at', '<=', $filters['date_to']);
        if (!empty($filters['course_id'])) {
            $course = Course::find($filters['course_id']);
            if ($course) $query->where('subject', 'like', '%'.$course->code.'%');
        }
        if (!empty($filters['status'])) {
            $query->whereHas('requestSubmissions', fn ($q) => $q->where('status', $filters['status']));
        }

        $documents = $query->latest()->paginate(20)->withQueryString();
        $documents->getCollection()->transform(function (Document $document) use ($term) {
            $document->search_excerpt = $this->excerpt((string) $document->searchIndex?->content_text, $term);
            return $document;
        });

        return view('document-search.index', [
            'documents' => $documents,
            'filters' => $filters,
            'employees' => User::with('employee')->where('status', 'Active')->whereHas('employee')->orderBy('username')->get(),
            'courses' => Course::active()->ordered()->get(),
            'schoolYears' => SchoolYear::orderByDesc('start_year')->get(),
            'savedSearches' => SavedDocumentSearch::where('user_id', $user->id)->latest()->get(),
        ]);
    }

    public function save(Request $request)
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:80'], 'filters' => ['required', 'array']]);
        SavedDocumentSearch::updateOrCreate(
            ['user_id' => $request->user()->id, 'name' => $validated['name']],
            ['filters' => collect($validated['filters'])->only(['q','employee_id','department','course_id','school_year_id','semester','status','type','date_from','date_to'])->filter(fn ($value) => $value !== null && $value !== '')->all()]
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
