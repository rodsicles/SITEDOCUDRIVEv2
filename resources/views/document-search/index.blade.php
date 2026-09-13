@extends('layouts.dashboard')

@section('title', 'Document Search')
@section('page-title', 'Document Search')
@section('page-subtitle', 'Search titles, metadata, Word files, PDFs, and scanned documents')

@section('sidebar')
    @if(auth()->user()->isProgramCoordinator()) @include('partials.coordinator-sidebar')
    @elseif(auth()->user()->isFaculty()) @include('partials.faculty-sidebar')
    @elseif(auth()->user()->isSecretary()) @include('partials.secretary-sidebar')
    @else @include('partials.dean-sidebar') @endif
@endsection

@section('content')
@php
    $viewer = auth()->user();
    $viewRoute = fn ($id) => route(($viewer->isProgramCoordinator() ? 'coordinator' : ($viewer->isFaculty() ? 'faculty' : 'dean')).'.view-document', $id);
    $searchTerm = trim((string) ($filters['q'] ?? ''));
    $highlight = function (string $text) use ($searchTerm) {
        $safe = e($text);
        if ($searchTerm === '') return $safe;
        return preg_replace('/('.preg_quote(e($searchTerm), '/').')/iu', '<mark>$1</mark>', $safe) ?? $safe;
    };
@endphp
<div class="search-workspace">
    @if(session('success'))<div class="workflow-alert workflow-alert--success" role="status"><i class="fas fa-check-circle"></i>{{ session('success') }}</div>@endif
    <form method="GET" action="{{ route('document-search.index') }}" class="document-search-form">
        <div class="document-search-primary"><label for="documentQuery">Search document content</label><div><i class="fas fa-search"></i><input id="documentQuery" name="q" value="{{ $searchTerm }}" placeholder="Search a phrase, title, subject, or tag…" autofocus><button class="btn btn-primary">Search</button></div></div>
        <details class="document-search-filters" {{ collect($filters)->except(['q','saved'])->filter()->isNotEmpty() ? 'open' : '' }}>
            <summary><i class="fas fa-sliders"></i> Advanced filters <span>Employee, department, course, term, status, type, and date</span></summary>
            <div class="document-search-filter-grid">
                <label>Employee<select name="employee_id"><option value="">All employees</option>@foreach($employees as $person)<option value="{{ $person->id }}" @selected((string)($filters['employee_id'] ?? '') === (string)$person->id)>{{ $person->employee->full_name ?? $person->username }}</option>@endforeach</select></label>
                <label>Department<select name="department"><option value="">All departments</option><option @selected(($filters['department'] ?? '') === 'Information Technology')>Information Technology</option><option @selected(($filters['department'] ?? '') === 'Engineering')>Engineering</option></select></label>
                <label>Course<select name="course_id"><option value="">All courses</option>@foreach($courses as $course)<option value="{{ $course->id }}" @selected((string)($filters['course_id'] ?? '') === (string)$course->id)>{{ $course->code }} — {{ $course->title }}</option>@endforeach</select></label>
                <label>School year<select name="school_year_id"><option value="">All school years</option>@foreach($schoolYears as $year)<option value="{{ $year->id }}" @selected((string)($filters['school_year_id'] ?? '') === (string)$year->id)>{{ $year->name }}</option>@endforeach</select></label>
                <label>Semester<select name="semester"><option value="">All semesters</option><option value="1st" @selected(($filters['semester'] ?? '') === '1st')>1st semester</option><option value="2nd" @selected(($filters['semester'] ?? '') === '2nd')>2nd semester</option></select></label>
                <label>Compliance status<select name="status"><option value="">All statuses</option>@foreach(['pending','submitted','changes_requested','approved'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ str($status)->replace('_',' ')->title() }}</option>@endforeach</select></label>
                <label>File type<select name="type"><option value="">All file types</option><option value="pdf" @selected(($filters['type'] ?? '') === 'pdf')>PDF</option><option value="doc" @selected(($filters['type'] ?? '') === 'doc')>Word</option><option value="image" @selected(($filters['type'] ?? '') === 'image')>Image</option></select></label>
                <label>Uploaded from<input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></label>
                <label>Uploaded to<input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></label>
            </div>
            <div class="document-search-filter-actions"><a href="{{ route('document-search.index') }}" class="btn btn-secondary">Clear filters</a><button class="btn btn-primary">Apply filters</button></div>
        </details>
    </form>

    <div class="saved-search-bar">
        <div><strong>Saved searches</strong>@forelse($savedSearches as $saved)<span class="saved-search-chip"><a href="{{ route('document-search.index', ['saved' => $saved->id]) }}">{{ $saved->name }}</a><form method="POST" action="{{ route('document-search.saved.destroy', $saved) }}">@csrf @method('DELETE')<button aria-label="Delete {{ $saved->name }}"><i class="fas fa-times"></i></button></form></span>@empty<span>No saved searches</span>@endforelse</div>
        @if($searchTerm !== '' || collect($filters)->except(['saved'])->filter()->isNotEmpty())<button type="button" class="btn btn-secondary" data-open-modal="saveSearchModal"><i class="far fa-bookmark"></i> Save this search</button>@endif
    </div>

    <section class="search-results" aria-label="Search results">
        <header><div><h2>{{ number_format($documents->total()) }} result{{ $documents->total() === 1 ? '' : 's' }}</h2><p>{{ $searchTerm ? 'Matches for “'.$searchTerm.'”' : 'Visible documents matching the selected filters' }}</p></div><span>Content indexing: PDF · Word · OCR</span></header>
        @forelse($documents as $document)
        <article class="search-result-row">
            <div class="search-result-icon"><i class="fas {{ str_contains(strtolower((string) $document->document_type), 'pdf') ? 'fa-file-pdf' : (str_contains(strtolower((string) $document->document_type), 'doc') ? 'fa-file-word' : 'fa-file') }}"></i></div>
            <div class="search-result-copy"><h3><a href="{{ $viewRoute($document->document_id) }}">{!! $highlight($document->document_title) !!}</a></h3>@if($document->search_excerpt)<p>{!! $highlight($document->search_excerpt) !!}</p>@elseif($searchTerm)<p class="search-result-unindexed">No extracted-text match. The title or metadata matched.</p>@endif<div><span>{{ $document->uploader->employee->full_name ?? $document->uploader->username }}</span><span>{{ $document->subject ?: $document->category ?: 'Document' }}</span><span>{{ strtoupper($document->document_type ?: 'FILE') }}</span><span>{{ $document->created_at->format('M d, Y') }}</span>@if($document->searchIndex?->index_status === 'no_text')<span>OCR found no readable text</span>@elseif(!$document->searchIndex)<span>Awaiting content index</span>@endif</div></div>
            <a href="{{ $viewRoute($document->document_id) }}" class="btn btn-secondary"><i class="fas fa-arrow-right"></i> Open</a>
        </article>
        @empty
            @include('partials.ui.empty-state', ['title' => 'No matching documents', 'text' => 'Try a shorter phrase or remove one of the advanced filters.'])
        @endforelse
        {{ $documents->links() }}
    </section>
</div>

<div id="saveSearchModal" class="modal-overlay" hidden role="dialog" aria-modal="true" aria-labelledby="saveSearchTitle"><div class="modal-card"><div class="modal-header"><div><h2 id="saveSearchTitle" class="modal-title">Save this search</h2><p>Reuse this exact set of filters later.</p></div><button type="button" class="modal-close" data-close-modal><i class="fas fa-times"></i></button></div><form method="POST" action="{{ route('document-search.saved.store') }}">@csrf @foreach(collect($filters)->except('saved') as $key => $value)@if($value !== null && $value !== '')<input type="hidden" name="filters[{{ $key }}]" value="{{ $value }}">@endif @endforeach<div class="modal-body"><label class="form-label">Search name *</label><input name="name" class="form-control" maxlength="80" required placeholder="e.g. Pending IT teaching guides"></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-close-modal>Cancel</button><button class="btn btn-primary">Save search</button></div></form></div></div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-open-modal]').forEach(function (button) { button.addEventListener('click', function () { var modal = document.getElementById(this.dataset.openModal); modal.hidden = false; modal.classList.add('active'); }); });
document.querySelectorAll('[data-close-modal]').forEach(function (button) { button.addEventListener('click', function () { var modal = this.closest('.modal-overlay'); modal.classList.remove('active'); modal.hidden = true; }); });
</script>
@endpush
