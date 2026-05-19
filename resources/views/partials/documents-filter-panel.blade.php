{{--
    Reusable documents toolbar (Faculty / Dean / Coordinator).
    @param string $documentsRoute Route name for GET filter form
    @param bool $showSchoolYearFilter Show academic year selector
--}}
@php
    use App\Support\AcademicYear;

    $documentsRoute = $documentsRoute ?? 'faculty.documents';
    $showSchoolYearFilter = $showSchoolYearFilter ?? false;
    $nameValue = request('name', request('search'));
    $sortValue = request('sort', 'date');
    $sortDirValue = request('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc';

    $sortLabels = [
        'date' => 'Date modified',
        'title' => 'Name',
        'size' => 'Size',
        'author' => 'Authors',
        'category' => 'Categories',
    ];

    $typeLabels = ['pdf' => 'PDF', 'word' => 'Word'];
    $sizeLabels = [
        'small' => 'Under 1 MB',
        'medium' => '1 MB – 5 MB',
        'large' => 'Over 5 MB',
    ];

    $docQuery = fn (array $except = []) => array_filter(
        array_merge(
            request()->except(array_merge(['page'], $except)),
            ['folder' => $folderFilter, 'tab' => $tab]
        ),
        fn ($v) => $v !== null && $v !== ''
    );

    $chips = [];
    if ($nameValue) {
        $chips[] = ['label' => 'Search: ' . $nameValue, 'except' => ['name', 'search']];
    }
    if (request('title') && request('title') !== $nameValue) {
        $chips[] = ['label' => 'Title: ' . request('title'), 'except' => ['title']];
    }
    if ($ft = request('file_type')) {
        $chips[] = ['label' => $typeLabels[$ft] ?? strtoupper($ft), 'except' => ['file_type']];
    }
    if (request()->filled('category')) {
        $chips[] = ['label' => 'Category: ' . request('category'), 'except' => ['category']];
    }
    if ($uid = request('uploaded_by')) {
        $uploaderName = $uploaders->firstWhere('id', (int) $uid)?->employee?->full_name
            ?? $uploaders->firstWhere('id', (int) $uid)?->username
            ?? 'Author';
        $chips[] = ['label' => 'Author: ' . $uploaderName, 'except' => ['uploaded_by']];
    }
    if (request('date_from') || request('date_to')) {
        $range = trim((request('date_from') ?: '…') . ' – ' . (request('date_to') ?: '…'));
        $chips[] = ['label' => 'Date: ' . $range, 'except' => ['date_from', 'date_to']];
    }
    if ($sr = request('size_range')) {
        $chips[] = ['label' => $sizeLabels[$sr] ?? $sr, 'except' => ['size_range']];
    }
    if ($showSchoolYearFilter && request()->filled('academic_year')) {
        $yearKey = (string) request('academic_year');
        $chips[] = [
            'label' => AcademicYear::options()[$yearKey] ?? ('AY ' . $yearKey),
            'except' => ['academic_year'],
        ];
    }
    if ($sortValue !== 'date' || $sortDirValue !== 'desc') {
        $order = $sortDirValue === 'asc' ? 'Ascending' : 'Descending';
        $chips[] = [
            'label' => 'Sort: ' . ($sortLabels[$sortValue] ?? $sortValue) . ' (' . $order . ')',
            'except' => ['sort', 'sort_dir'],
        ];
    }

    $resetUrl = route($documentsRoute, array_filter(['folder' => $folderFilter, 'tab' => $tab]));
@endphp

<div class="card-header card-header--documents doc-toolbar-header">
    <h3 class="card-title mb-0">Available Documents</h3>
    <span class="badge badge-info">{{ $documents->total() }} Files</span>
</div>

<div class="doc-toolbar" data-doc-toolbar>
    <form action="{{ route($documentsRoute) }}" method="GET" class="doc-toolbar-form" id="docToolbarForm">
        <input type="hidden" name="folder" value="{{ $folderFilter }}">
        <input type="hidden" name="tab" value="{{ $tab }}">

        <div class="doc-toolbar-row">
            <label class="doc-toolbar-search" for="docToolbarSearch">
                <i class="fas fa-search doc-toolbar-search-icon" aria-hidden="true"></i>
                <input type="search"
                       id="docToolbarSearch"
                       name="name"
                       value="{{ $nameValue }}"
                       class="form-control text-sm"
                       placeholder="Search documents..."
                       autocomplete="off">
            </label>

            <div class="doc-toolbar-actions">
                {{-- Sort --}}
                <div class="doc-menu" data-doc-menu>
                    <button type="button"
                            class="btn btn-sm doc-toolbar-menu-btn {{ ($sortValue !== 'date' || $sortDirValue !== 'desc') ? 'is-active' : '' }}"
                            data-doc-menu-trigger
                            aria-haspopup="true"
                            aria-expanded="false">
                        <i class="fas fa-arrow-down-wide-short" aria-hidden="true"></i>
                        <span>Sort</span>
                        <i class="fas fa-chevron-down doc-toolbar-chevron" aria-hidden="true"></i>
                    </button>
                    <div class="doc-menu-panel" data-doc-menu-panel role="menu">
                        <p class="doc-menu-heading">Sort by</p>
                        @foreach($sortLabels as $value => $label)
                        <label class="doc-menu-option">
                            <input type="radio" name="sort" value="{{ $value }}" @checked($sortValue === $value) data-doc-auto-submit>
                            <span>{{ $label }}</span>
                        </label>
                        @endforeach
                        <div class="doc-menu-divider" role="separator"></div>
                        <p class="doc-menu-heading">Order</p>
                        <label class="doc-menu-option">
                            <input type="radio" name="sort_dir" value="asc" @checked($sortDirValue === 'asc') data-doc-auto-submit>
                            <span>Ascending</span>
                        </label>
                        <label class="doc-menu-option">
                            <input type="radio" name="sort_dir" value="desc" @checked($sortDirValue === 'desc') data-doc-auto-submit>
                            <span>Descending</span>
                        </label>
                    </div>
                </div>

                {{-- Filter --}}
                <div class="doc-menu" data-doc-menu>
                    <button type="button"
                            class="btn btn-sm doc-toolbar-menu-btn {{ collect(request()->only(['file_type', 'category', 'uploaded_by', 'date_from', 'date_to', 'size_range', 'title']))->filter()->isNotEmpty() ? 'is-active' : '' }}"
                            data-doc-menu-trigger
                            aria-haspopup="true"
                            aria-expanded="false">
                        <i class="fas fa-filter" aria-hidden="true"></i>
                        <span>Filter</span>
                        <i class="fas fa-chevron-down doc-toolbar-chevron" aria-hidden="true"></i>
                    </button>
                    <div class="doc-menu-panel doc-menu-panel--wide" data-doc-menu-panel role="menu">
                        <p class="doc-menu-heading">Refine results</p>
                        <div class="doc-menu-field">
                            <label class="doc-menu-label" for="docFilterType">Type</label>
                            <select id="docFilterType" name="file_type" class="form-control text-sm">
                                <option value="">All types</option>
                                <option value="pdf" @selected(request('file_type') === 'pdf')>PDF</option>
                                <option value="word" @selected(request('file_type') === 'word')>Word</option>
                            </select>
                        </div>
                        <div class="doc-menu-field">
                            <label class="doc-menu-label" for="docFilterTitle">Title contains</label>
                            <input type="text" id="docFilterTitle" name="title" value="{{ request('title') }}" class="form-control text-sm" placeholder="Optional title filter">
                        </div>
                        <div class="doc-menu-field">
                            <label class="doc-menu-label" for="docFilterSize">Size</label>
                            <select id="docFilterSize" name="size_range" class="form-control text-sm">
                                <option value="">Any size</option>
                                @foreach($sizeLabels as $val => $lbl)
                                <option value="{{ $val }}" @selected(request('size_range') === $val)>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="doc-menu-grid">
                            <div class="doc-menu-field">
                                <label class="doc-menu-label" for="docFilterDateFrom">From</label>
                                <input type="date" id="docFilterDateFrom" name="date_from" value="{{ request('date_from') }}" class="form-control text-sm">
                            </div>
                            <div class="doc-menu-field">
                                <label class="doc-menu-label" for="docFilterDateTo">To</label>
                                <input type="date" id="docFilterDateTo" name="date_to" value="{{ request('date_to') }}" class="form-control text-sm">
                            </div>
                        </div>
                        <div class="doc-menu-field">
                            <label class="doc-menu-label" for="docFilterAuthor">Author</label>
                            <select id="docFilterAuthor" name="uploaded_by" class="form-control text-sm">
                                <option value="">All authors</option>
                                @foreach($uploaders as $uploader)
                                <option value="{{ $uploader->id }}" @selected((string) request('uploaded_by') === (string) $uploader->id)>
                                    {{ $uploader->employee->full_name ?? $uploader->username }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="doc-menu-field">
                            <label class="doc-menu-label" for="docFilterCategory">Category</label>
                            <select id="docFilterCategory" name="category" class="form-control text-sm">
                                <option value="">All categories</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="doc-menu-footer">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-check"></i> Apply filters
                            </button>
                            <a href="{{ $resetUrl }}" class="btn btn-sm bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">Clear filters</a>
                        </div>
                    </div>
                </div>

                {{-- Saved views --}}
                <div class="doc-menu" data-doc-menu>
                    <button type="button"
                            class="btn btn-sm doc-toolbar-menu-btn {{ $savedFilters->isNotEmpty() ? 'is-active' : '' }}"
                            data-doc-menu-trigger
                            aria-haspopup="true"
                            aria-expanded="false">
                        <i class="fas fa-bookmark" aria-hidden="true"></i>
                        <span>Views</span>
                        <i class="fas fa-chevron-down doc-toolbar-chevron" aria-hidden="true"></i>
                    </button>
                    <div class="doc-menu-panel" data-doc-menu-panel role="menu">
                        @if($savedFilters->isNotEmpty())
                        <p class="doc-menu-heading">Saved views</p>
                        @foreach($savedFilters as $savedFilter)
                        <div class="doc-menu-view-row">
                            <a href="{{ route($documentsRoute, array_filter(array_merge($savedFilter->toQueryParams(), ['folder' => $folderFilter, 'tab' => $tab, 'saved_filter' => $savedFilter->document_filter_id]))) }}"
                               class="doc-menu-view-link"
                               role="menuitem">
                                {{ $savedFilter->name }}
                            </a>
                            <form action="{{ route('document-filters.destroy', $savedFilter->document_filter_id) }}" method="POST" class="doc-menu-view-delete">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="doc-menu-view-delete-btn" title="Delete view" aria-label="Delete {{ $savedFilter->name }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                        @endforeach
                        <div class="doc-menu-divider" role="separator"></div>
                        @endif
                        <p class="doc-menu-heading">Save current view</p>
                        <div class="doc-menu-save-row">
                            <input type="text"
                                   name="view_name"
                                   form="docSaveViewForm"
                                   class="form-control text-sm"
                                   placeholder="View name"
                                   maxlength="50"
                                   required
                                   aria-label="View name">
                            <button type="submit" form="docSaveViewForm" class="btn btn-sm btn-primary">
                                Save
                            </button>
                        </div>
                    </div>
                </div>

                @if($showSchoolYearFilter)
                <label class="doc-toolbar-year">
                    <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                    <select name="academic_year" class="form-control text-sm" data-doc-auto-submit aria-label="School year">
                        <option value="">All years</option>
                        @foreach(AcademicYear::options() as $startYear => $label)
                            @php $isArchive = AcademicYear::isArchived((int) $startYear); @endphp
                            <option value="{{ $startYear }}" @selected((string) request('academic_year') === (string) $startYear)>
                                {{ $label }}{{ $isArchive ? ' (Archive)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </label>
                @endif

                <button type="submit" class="btn btn-sm btn-primary" title="Search" aria-label="Search documents">
                    <i class="fas fa-search" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        @if(count($chips) > 0)
        <div class="doc-toolbar-chips" aria-label="Active filters">
            @foreach($chips as $chip)
            <a href="{{ route($documentsRoute, $docQuery($chip['except'])) }}" class="doc-chip">
                <span>{{ $chip['label'] }}</span>
                <i class="fas fa-times doc-chip-remove" aria-hidden="true"></i>
            </a>
            @endforeach
            <a href="{{ $resetUrl }}" class="doc-chip doc-chip--clear">Clear all</a>
        </div>
        @endif
    </form>

    <form action="{{ route('document-filters.store') }}" method="POST" id="docSaveViewForm" class="hidden">
        @csrf
        <input type="hidden" name="name" value="" data-doc-save-view-name>
        <input type="hidden" name="folder" value="{{ $folderFilter }}">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input type="hidden" name="search" value="{{ $nameValue }}">
        <input type="hidden" name="file_type" value="{{ request('file_type') }}">
        <input type="hidden" name="uploaded_by" value="{{ request('uploaded_by') }}">
        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
        <input type="hidden" name="size_range" value="{{ request('size_range') }}">
        <input type="hidden" name="sort" value="{{ $sortValue }}">
        <input type="hidden" name="sort_dir" value="{{ $sortDirValue }}">
        <input type="hidden" name="title" value="{{ request('title') }}">
        <input type="hidden" name="category" value="{{ request('category', $categoryFilter ?? '') }}">
        @if($showSchoolYearFilter)
        <input type="hidden" name="academic_year" value="{{ request('academic_year') }}">
        @endif
    </form>
</div>

@push('scripts')
<script>
(function () {
    var toolbar = document.querySelector('[data-doc-toolbar]');
    if (!toolbar) return;

    var form = document.getElementById('docToolbarForm');
    var saveForm = document.getElementById('docSaveViewForm');
    var saveNameHidden = saveForm && saveForm.querySelector('[data-doc-save-view-name]');
    var viewNameInput = toolbar.querySelector('input[name="view_name"]');

    function closeAllMenus(exceptPanel) {
        toolbar.querySelectorAll('[data-doc-menu-panel]').forEach(function (panel) {
            if (exceptPanel && panel === exceptPanel) return;
            panel.classList.remove('is-open');
        });
        toolbar.querySelectorAll('[data-doc-menu-trigger]').forEach(function (btn) {
            btn.setAttribute('aria-expanded', 'false');
        });
    }

    toolbar.querySelectorAll('[data-doc-menu]').forEach(function (menu) {
        var trigger = menu.querySelector('[data-doc-menu-trigger]');
        var panel = menu.querySelector('[data-doc-menu-panel]');
        if (!trigger || !panel) return;

        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            var willOpen = !panel.classList.contains('is-open');
            closeAllMenus();
            if (willOpen) {
                panel.classList.add('is-open');
                trigger.setAttribute('aria-expanded', 'true');
            }
        });

        panel.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    });

    document.addEventListener('click', function () {
        closeAllMenus();
    });

    toolbar.querySelectorAll('[data-doc-auto-submit]').forEach(function (el) {
        el.addEventListener('change', function () {
            if (form) form.submit();
        });
    });

    if (saveForm && viewNameInput && saveNameHidden) {
        saveForm.addEventListener('submit', function (e) {
            var name = (viewNameInput.value || '').trim();
            if (!name) {
                e.preventDefault();
                viewNameInput.focus();
                return;
            }
            saveNameHidden.value = name;
        });
    }
})();
</script>
@endpush
