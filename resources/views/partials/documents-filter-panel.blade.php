{{--
    Reusable documents toolbar (Faculty / Dean / Coordinator).
    @param string $documentsRoute Route name for GET filter form
--}}
@php
    $documentsRoute = $documentsRoute ?? 'faculty.documents';
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
    if (request('date_from') || request('date_to')) {
        $range = trim((request('date_from') ?: '…') . ' – ' . (request('date_to') ?: '…'));
        $chips[] = ['label' => 'Date: ' . $range, 'except' => ['date_from', 'date_to']];
    }
    if ($sr = request('size_range')) {
        $chips[] = ['label' => $sizeLabels[$sr] ?? $sr, 'except' => ['size_range']];
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
                            class="btn btn-sm doc-toolbar-menu-btn {{ collect(request()->only(['file_type', 'category', 'date_from', 'date_to', 'size_range', 'title']))->filter()->isNotEmpty() ? 'is-active' : '' }}"
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
                        <div class="doc-menu-date-range">
                            <div class="doc-menu-field">
                                <label class="doc-menu-label" for="docFilterDateFrom">From</label>
                                <input type="date" id="docFilterDateFrom" name="date_from" value="{{ request('date_from') }}" class="form-control text-sm doc-menu-date-input">
                            </div>
                            <div class="doc-menu-field">
                                <label class="doc-menu-label" for="docFilterDateTo">To</label>
                                <input type="date" id="docFilterDateTo" name="date_to" value="{{ request('date_to') }}" class="form-control text-sm doc-menu-date-input">
                            </div>
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
</div>

@push('scripts')
<script>
(function () {
    var toolbar = document.querySelector('[data-doc-toolbar]');
    if (!toolbar) return;

    var form = document.getElementById('docToolbarForm');

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
})();
</script>
@endpush
