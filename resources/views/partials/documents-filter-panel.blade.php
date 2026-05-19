@php
    use App\Support\AcademicYear;
    use App\Support\DocumentListSortUrls;

    $documentsRoute = $documentsRoute ?? 'faculty.documents';
    $showSchoolYearFilter = $showSchoolYearFilter ?? false;
    $folderFilter = $folderFilter ?? null;
    $tab = $tab ?? request('tab', 'accreditation');
    $savedFilters = $savedFilters ?? collect();

    $currentSort = request('sort', 'date');
    $currentType = request('file_type', '');
    $currentSearch = request('search', request('name', ''));

    $moreSortActive = in_array($currentSort, ['size', 'author', 'category'], true)
        || request()->filled('academic_year');

    $hasActiveFilters = $currentType !== ''
        || ($currentSort !== '' && $currentSort !== 'date')
        || request()->filled('academic_year');

    $sortLabels = [
        'title' => 'Name',
        'date' => 'Date',
        'size' => 'Size',
        'author' => 'Authors',
        'category' => 'Categories',
    ];

    $sortButtonLabel = 'Sort';
    if ($currentType === 'pdf') {
        $sortButtonLabel = 'Sort · PDF';
    } elseif ($currentType === 'word') {
        $sortButtonLabel = 'Sort · Word';
    } elseif ($currentSort !== 'date' && isset($sortLabels[$currentSort])) {
        $sortButtonLabel = 'Sort · '.$sortLabels[$currentSort];
    }

    $req = request();
    $searchPreserve = array_filter([
        'folder' => $folderFilter,
        'tab' => $tab,
        'sort' => request('sort'),
        'file_type' => request('file_type'),
        'academic_year' => request('academic_year'),
    ], fn ($v) => $v !== null && $v !== '');
@endphp

<div class="card-header card-header--documents">
    <div class="card-header-documents-left">
        <h3 class="card-title mb-0">Available Documents</h3>

        <div class="doc-header-toolbar">
            <form action="{{ route($documentsRoute) }}" method="GET" class="doc-search-form" role="search">
                @foreach($searchPreserve as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <label class="sr-only" for="docSearchInput">Search documents</label>
                <i class="fas fa-search doc-search-icon" aria-hidden="true"></i>
                <input type="search"
                       id="docSearchInput"
                       name="search"
                       value="{{ $currentSearch }}"
                       class="doc-search-input"
                       placeholder="Search documents..."
                       autocomplete="off">
                @if($currentSearch !== '')
                <a href="{{ route($documentsRoute, $searchPreserve) }}"
                   class="doc-search-clear"
                   aria-label="Clear search"
                   title="Clear search">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </a>
                @endif
            </form>

            <div class="doc-sort-wrap" id="docSortWrap">
                <button type="button"
                        class="doc-sort-trigger {{ $hasActiveFilters ? 'is-active' : '' }}"
                        id="docSortBtn"
                        aria-haspopup="true"
                        aria-expanded="false"
                        aria-controls="docSortMenu">
                    <i class="fas fa-sort" aria-hidden="true"></i>
                    <span>{{ $sortButtonLabel }}</span>
                    <i class="fas fa-chevron-down doc-sort-chevron" aria-hidden="true"></i>
                </button>

                <div class="doc-sort-menu hidden" id="docSortMenu" role="menu">
                    <a href="{{ DocumentListSortUrls::sortHref($documentsRoute, $req, $folderFilter, $tab, 'title') }}"
                       class="doc-sort-item {{ $currentSort === 'title' && !$currentType ? 'is-active' : '' }}"
                       role="menuitem">
                        @if($currentSort === 'title' && !$currentType)<span class="doc-sort-dot" aria-hidden="true"></span>@endif
                        <span>Name</span>
                    </a>
                    <a href="{{ DocumentListSortUrls::sortHref($documentsRoute, $req, $folderFilter, $tab, 'date') }}"
                       class="doc-sort-item {{ $currentSort === 'date' && !$currentType ? 'is-active' : '' }}"
                       role="menuitem">
                        @if($currentSort === 'date' && !$currentType)<span class="doc-sort-dot" aria-hidden="true"></span>@endif
                        <span>Date</span>
                    </a>

                    <div class="doc-sort-item has-submenu" data-submenu="type" role="none">
                        <button type="button" class="doc-sort-item-row {{ $currentType || $moreSortActive ? 'is-active' : '' }}" aria-haspopup="true" aria-expanded="false">
                            @if($currentType || $moreSortActive)<span class="doc-sort-dot" aria-hidden="true"></span>@endif
                            <span>Type</span>
                            <i class="fas fa-chevron-right doc-sort-submenu-chevron" aria-hidden="true"></i>
                        </button>
                        <div class="doc-sort-flyout" role="menu">
                            <a href="{{ DocumentListSortUrls::typeHref($documentsRoute, $req, $folderFilter, $tab, null) }}"
                               class="doc-sort-flyout-item {{ !$currentType && !$moreSortActive ? 'is-active' : '' }}"
                               role="menuitem">All types</a>
                            <a href="{{ DocumentListSortUrls::typeHref($documentsRoute, $req, $folderFilter, $tab, 'pdf') }}"
                               class="doc-sort-flyout-item {{ $currentType === 'pdf' ? 'is-active' : '' }}"
                               role="menuitem">PDF</a>
                            <a href="{{ DocumentListSortUrls::typeHref($documentsRoute, $req, $folderFilter, $tab, 'word') }}"
                               class="doc-sort-flyout-item {{ $currentType === 'word' ? 'is-active' : '' }}"
                               role="menuitem">Word</a>

                            <div class="doc-sort-flyout-item has-submenu" data-submenu="more" role="none">
                                <button type="button" class="doc-sort-flyout-row {{ $moreSortActive ? 'is-active' : '' }}" aria-haspopup="true" aria-expanded="false">
                                    <span>More</span>
                                    <i class="fas fa-chevron-right doc-sort-submenu-chevron" aria-hidden="true"></i>
                                </button>
                                <div class="doc-sort-flyout doc-sort-flyout--nested" role="menu">
                                    <a href="{{ DocumentListSortUrls::sortHref($documentsRoute, $req, $folderFilter, $tab, 'size') }}"
                                       class="doc-sort-flyout-item {{ $currentSort === 'size' ? 'is-active' : '' }}"
                                       role="menuitem">Size</a>
                                    <a href="{{ DocumentListSortUrls::sortHref($documentsRoute, $req, $folderFilter, $tab, 'author') }}"
                                       class="doc-sort-flyout-item {{ $currentSort === 'author' ? 'is-active' : '' }}"
                                       role="menuitem">Authors</a>
                                    <a href="{{ DocumentListSortUrls::sortHref($documentsRoute, $req, $folderFilter, $tab, 'category') }}"
                                       class="doc-sort-flyout-item {{ $currentSort === 'category' ? 'is-active' : '' }}"
                                       role="menuitem">Categories</a>
                                    <a href="{{ DocumentListSortUrls::sortHref($documentsRoute, $req, $folderFilter, $tab, 'title') }}"
                                       class="doc-sort-flyout-item {{ $currentSort === 'title' && ($currentType || $moreSortActive) ? 'is-active' : '' }}"
                                       role="menuitem">Title</a>
                                    @if($showSchoolYearFilter)
                                    <div class="doc-sort-flyout-divider" role="separator"></div>
                                    <span class="doc-sort-flyout-label">School year</span>
                                    <a href="{{ DocumentListSortUrls::yearHref($documentsRoute, $req, $folderFilter, $tab, '') }}"
                                       class="doc-sort-flyout-item {{ !request('academic_year') ? 'is-active' : '' }}"
                                       role="menuitem">All years</a>
                                    @foreach(AcademicYear::options() as $startYear => $label)
                                    <a href="{{ DocumentListSortUrls::yearHref($documentsRoute, $req, $folderFilter, $tab, (string) $startYear) }}"
                                       class="doc-sort-flyout-item {{ (string) request('academic_year') === (string) $startYear ? 'is-active' : '' }}"
                                       role="menuitem">{{ $label }}</a>
                                    @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($hasActiveFilters)
                    <div class="doc-sort-menu-footer" role="none">
                        <a href="{{ DocumentListSortUrls::resetHref($documentsRoute, $folderFilter, $tab, $req) }}" class="doc-sort-reset" role="menuitem">
                            <i class="fas fa-rotate-left" aria-hidden="true"></i> Reset sort &amp; type
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <span class="badge badge-info">{{ $documents->total() }} Files</span>
</div>

@if($savedFilters->isNotEmpty())
<div class="doc-saved-presets px-4 pb-2">
    @foreach($savedFilters as $savedFilter)
    <a href="{{ route($documentsRoute, array_merge($savedFilter->toQueryParams(), ['saved_filter' => $savedFilter->document_filter_id, 'folder' => $folderFilter, 'tab' => $tab])) }}"
       class="doc-saved-preset-chip">{{ $savedFilter->name }}</a>
    @endforeach
</div>
@endif

@push('scripts')
<script>
(function () {
    var wrap = document.getElementById('docSortWrap');
    var btn = document.getElementById('docSortBtn');
    var menu = document.getElementById('docSortMenu');
    if (!wrap || !btn || !menu) return;

    function closeAllSubmenus() {
        menu.querySelectorAll('.has-submenu.submenu-open').forEach(function (el) {
            el.classList.remove('submenu-open');
            var toggle = el.querySelector('[aria-expanded]');
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
        });
    }

    function closeMenu() {
        menu.classList.add('hidden');
        btn.setAttribute('aria-expanded', 'false');
        closeAllSubmenus();
    }

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        var opening = menu.classList.contains('hidden');
        if (opening) {
            menu.classList.remove('hidden');
            btn.setAttribute('aria-expanded', 'true');
        } else {
            closeMenu();
        }
    });

    document.addEventListener('click', function (e) {
        if (!wrap.contains(e.target)) {
            closeMenu();
        }
    });

    menu.querySelectorAll('.has-submenu').forEach(function (item) {
        var toggle = item.querySelector('.doc-sort-item-row, .doc-sort-flyout-row');
        if (!toggle) return;

        function openSubmenu() {
            var parent = item.parentElement;
            if (parent) {
                parent.querySelectorAll(':scope > .has-submenu.submenu-open').forEach(function (sibling) {
                    if (sibling !== item) {
                        sibling.classList.remove('submenu-open');
                        var sibToggle = sibling.querySelector('[aria-expanded]');
                        if (sibToggle) sibToggle.setAttribute('aria-expanded', 'false');
                    }
                });
            }
            item.classList.add('submenu-open');
            toggle.setAttribute('aria-expanded', 'true');
        }

        item.addEventListener('mouseenter', openSubmenu);
        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (item.classList.contains('submenu-open')) {
                item.classList.remove('submenu-open');
                toggle.setAttribute('aria-expanded', 'false');
            } else {
                openSubmenu();
            }
        });
    });

    var searchForm = wrap.closest('.doc-header-toolbar');
    if (searchForm) {
        searchForm = searchForm.querySelector('.doc-search-form');
    }
    if (searchForm) {
        var searchInput = searchForm.querySelector('.doc-search-input');
        if (searchInput) {
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    searchForm.submit();
                }
            });
        }
    }
})();
</script>
@endpush
