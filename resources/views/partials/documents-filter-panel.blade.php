@php
    $documentsRoute = $documentsRoute ?? 'faculty.documents';
    $showSchoolYearFilter = $showSchoolYearFilter ?? false;
    $hasActiveFilters = collect(request()->only([
        'name', 'search', 'file_type', 'category', 'uploaded_by', 'date_from', 'date_to',
        'size_range', 'sort', 'title',
    ]))->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
    $nameValue = request('name', request('search'));
@endphp

<div class="card-header card-header--documents">
    <div class="card-header-documents-left">
        <h3 class="card-title mb-0">Available Documents</h3>
        <button type="button"
                class="btn btn-sm doc-filter-toggle {{ $hasActiveFilters ? 'is-active' : '' }}"
                onclick="toggleDocFilters()"
                aria-expanded="false"
                aria-controls="docFiltersPanel"
                id="docFiltersToggle">
            <i class="fas fa-filter" aria-hidden="true"></i> Filters
            @if($hasActiveFilters)
                <span class="doc-filter-active-dot" aria-label="Filters active"></span>
            @endif
        </button>
    </div>
    <span class="badge badge-info">{{ $documents->total() }} Files</span>
</div>

<div id="docFiltersPanel" class="documents-filter doc-filter-panel" style="display:none;">
    <form action="{{ route($documentsRoute) }}" method="GET" class="doc-filter-form">
        <input type="hidden" name="folder" value="{{ $folderFilter }}">
        <input type="hidden" name="tab" value="{{ $tab }}">

        <div class="doc-filter-primary">
            <div class="doc-filter-field">
                <label class="doc-filter-label" for="docFilterName">Name</label>
                <input type="text" id="docFilterName" name="name" value="{{ $nameValue }}" class="form-control text-sm" placeholder="Search by name...">
            </div>
            <div class="doc-filter-field">
                <label class="doc-filter-label" for="docFilterType">Type</label>
                <select id="docFilterType" name="file_type" class="form-control text-sm">
                    <option value="">All types</option>
                    <option value="pdf" @selected(request('file_type') === 'pdf')>PDF</option>
                    <option value="word" @selected(request('file_type') === 'word')>Word</option>
                </select>
            </div>
            <div class="doc-filter-field doc-filter-more-wrap">
                <label class="doc-filter-label">More</label>
                <button type="button" class="form-control text-sm doc-filter-more-btn" onclick="toggleDocMoreMenu(event)" aria-haspopup="true" aria-expanded="false" id="docMoreMenuBtn">
                    <span>Options</span>
                    <i class="fas fa-chevron-down text-xs ml-1"></i>
                </button>
                <div id="docMoreMenu" class="doc-filter-more-menu hidden" role="menu">
                    <p class="doc-filter-more-heading">Sort by</p>
                    <label class="doc-filter-more-option">
                        <input type="radio" name="sort" value="size" @checked(request('sort') === 'size')>
                        <span>Size</span>
                    </label>
                    <label class="doc-filter-more-option">
                        <input type="radio" name="sort" value="date" @checked(request('sort', 'date') === 'date')>
                        <span>Date created</span>
                    </label>
                    <label class="doc-filter-more-option">
                        <input type="radio" name="sort" value="author" @checked(request('sort') === 'author')>
                        <span>Authors</span>
                    </label>
                    <label class="doc-filter-more-option">
                        <input type="radio" name="sort" value="category" @checked(request('sort') === 'category')>
                        <span>Categories</span>
                    </label>
                    <label class="doc-filter-more-option">
                        <input type="radio" name="sort" value="title" @checked(request('sort') === 'title')>
                        <span>Title</span>
                    </label>

                    <p class="doc-filter-more-heading mt-3">Refine</p>
                    <label class="doc-filter-label text-xs">Title</label>
                    <input type="text" name="title" value="{{ request('title') }}" class="form-control text-sm mb-2" placeholder="Title contains...">
                    <label class="doc-filter-label text-xs">Size</label>
                    <select name="size_range" class="form-control text-sm mb-2">
                        <option value="">Any size</option>
                        <option value="small" @selected(request('size_range') === 'small')>Under 1 MB</option>
                        <option value="medium" @selected(request('size_range') === 'medium')>1 MB – 5 MB</option>
                        <option value="large" @selected(request('size_range') === 'large')>Over 5 MB</option>
                    </select>
                    <label class="doc-filter-label text-xs">Date created (from)</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control text-sm mb-2">
                    <label class="doc-filter-label text-xs">Date created (to)</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control text-sm mb-2">
                    <label class="doc-filter-label text-xs">Authors</label>
                    <select name="uploaded_by" class="form-control text-sm mb-2">
                        <option value="">All authors</option>
                        @foreach($uploaders as $uploader)
                            <option value="{{ $uploader->id }}" @selected((string) request('uploaded_by') === (string) $uploader->id)>
                                {{ $uploader->employee->full_name ?? $uploader->username }}
                            </option>
                        @endforeach
                    </select>
                    <label class="doc-filter-label text-xs">Categories</label>
                    <select name="category" class="form-control text-sm">
                        <option value="">All categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" @selected($categoryFilter === $cat)>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="doc-filter-actions">
                <button type="submit" class="btn btn-primary text-sm">
                    <i class="fas fa-check"></i> Apply
                </button>
                <a href="{{ route($documentsRoute, array_filter(['folder' => $folderFilter, 'tab' => $tab])) }}" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm">
                    <i class="fas fa-rotate-left"></i> Reset
                </a>
            </div>
        </div>

        @if($showSchoolYearFilter)
        <div class="doc-filter-school-year mt-3">
            @include('partials.school-year-filter', ['selected' => request('academic_year', '')])
        </div>
        @endif
    </form>

    <div class="flex flex-col gap-3 border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
        <form action="{{ route('document-filters.store') }}" method="POST" class="flex flex-wrap items-end gap-2">
            @csrf
            <input type="hidden" name="category" value="{{ request('category', $categoryFilter) }}">
            <input type="hidden" name="folder" value="{{ $folderFilter }}">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <input type="hidden" name="search" value="{{ $nameValue }}">
            <input type="hidden" name="file_type" value="{{ request('file_type') }}">
            <input type="hidden" name="uploaded_by" value="{{ request('uploaded_by') }}">
            <input type="hidden" name="date_from" value="{{ request('date_from') }}">
            <input type="hidden" name="date_to" value="{{ request('date_to') }}">
            <input type="hidden" name="size_range" value="{{ request('size_range') }}">
            <input type="hidden" name="sort" value="{{ request('sort') }}">
            <input type="hidden" name="title" value="{{ request('title') }}">
            <div>
                <label class="text-xs font-semibold text-gray-600 dark:text-gray-300 block mb-1">Save Current Filters</label>
                <input type="text" name="name" class="form-control text-sm" placeholder="Preset name" maxlength="50" required>
            </div>
            <button type="submit" class="btn btn-primary text-sm">
                <i class="fas fa-bookmark"></i> Save Filter
            </button>
        </form>

        @if($savedFilters->isNotEmpty())
        <div class="flex flex-wrap gap-2">
            @foreach($savedFilters as $savedFilter)
            <div class="flex items-center gap-2 border border-gray-200 dark:border-gray-700 px-3 py-2 bg-gray-50 dark:bg-[#1e1e1e]">
                <a href="{{ route($documentsRoute, array_merge($savedFilter->toQueryParams(), ['saved_filter' => $savedFilter->document_filter_id])) }}" class="text-sm font-medium text-gray-700 dark:text-gray-200 no-underline">
                    {{ $savedFilter->name }}
                </a>
                <form action="{{ route('document-filters.destroy', $savedFilter->document_filter_id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs text-red-600 dark:text-red-400 bg-transparent border-0 cursor-pointer">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function toggleDocFilters() {
    var panel = document.getElementById('docFiltersPanel');
    var toggle = document.getElementById('docFiltersToggle');
    var open = panel.style.display === 'none' || panel.style.display === '';
    panel.style.display = open ? 'block' : 'none';
    if (toggle) {
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
}
function toggleDocMoreMenu(e) {
    if (e) e.stopPropagation();
    var menu = document.getElementById('docMoreMenu');
    var btn = document.getElementById('docMoreMenuBtn');
    if (!menu) return;
    menu.classList.toggle('hidden');
    if (btn) {
        btn.setAttribute('aria-expanded', menu.classList.contains('hidden') ? 'false' : 'true');
    }
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('.doc-filter-more-wrap')) {
        var menu = document.getElementById('docMoreMenu');
        if (menu) menu.classList.add('hidden');
    }
});
</script>
@endpush
