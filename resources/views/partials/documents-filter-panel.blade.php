@php
    use App\Support\AcademicYear;

    $documentsRoute = $documentsRoute ?? 'faculty.documents';
    $showSchoolYearFilter = $showSchoolYearFilter ?? false;

    $currentSort = request('sort', 'date');
    $currentType = request('file_type', '');

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

    $sortHref = function (string $sort) use ($documentsRoute, $folderFilter, $tab) {
        $params = request()->except('page');
        if ($sort === 'date') {
            unset($params['sort']);
        } else {
            $params['sort'] = $sort;
        }
        $params['folder'] = $folderFilter;
        $params['tab'] = $tab;

        return route($documentsRoute, array_filter($params, fn ($v) => $v !== null && $v !== ''));
    };

    $typeHref = function (?string $type) use ($documentsRoute, $folderFilter, $tab) {
        $params = request()->except('page', 'file_type');
        if ($type) {
            $params['file_type'] = $type;
        }
        $params['folder'] = $folderFilter;
        $params['tab'] = $tab;

        return route($documentsRoute, array_filter($params, fn ($v) => $v !== null && $v !== ''));
    };

    $yearHref = function (string $year) use ($documentsRoute, $folderFilter, $tab) {
        $params = request()->except('page');
        if ($year === '') {
            unset($params['academic_year']);
        } else {
            $params['academic_year'] = $year;
        }
        $params['folder'] = $folderFilter;
        $params['tab'] = $tab;

        return route($documentsRoute, array_filter($params, fn ($v) => $v !== null && $v !== ''));
    };

    $resetHref = route($documentsRoute, array_filter([
        'folder' => $folderFilter,
        'tab' => $tab,
    ], fn ($v) => $v !== null && $v !== ''));

    $sortButtonLabel = 'Sort';
    if ($currentType === 'pdf') {
        $sortButtonLabel = 'Sort · PDF';
    } elseif ($currentType === 'word') {
        $sortButtonLabel = 'Sort · Word';
    } elseif ($currentSort !== 'date' && isset($sortLabels[$currentSort])) {
        $sortButtonLabel = 'Sort · '.$sortLabels[$currentSort];
    }
@endphp

<div class="card-header card-header--documents">
    <div class="card-header-documents-left">
        <h3 class="card-title mb-0">Available Documents</h3>

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
                <a href="{{ $sortHref('title') }}" class="doc-sort-item {{ $currentSort === 'title' && !$currentType ? 'is-active' : '' }}" role="menuitem">
                    @if($currentSort === 'title' && !$currentType)<span class="doc-sort-dot" aria-hidden="true"></span>@endif
                    <span>Name</span>
                </a>
                <a href="{{ $sortHref('date') }}" class="doc-sort-item {{ $currentSort === 'date' && !$currentType ? 'is-active' : '' }}" role="menuitem">
                    @if($currentSort === 'date' && !$currentType)<span class="doc-sort-dot" aria-hidden="true"></span>@endif
                    <span>Date</span>
                </a>

                <div class="doc-sort-item has-submenu" role="none">
                    <span class="doc-sort-item-row {{ $currentType ? 'is-active' : '' }}">
                        @if($currentType)<span class="doc-sort-dot" aria-hidden="true"></span>@endif
                        <span>Type</span>
                        <i class="fas fa-chevron-right doc-sort-submenu-chevron" aria-hidden="true"></i>
                    </span>
                    <div class="doc-sort-submenu" role="menu">
                        <a href="{{ $typeHref(null) }}" class="doc-sort-subitem {{ !$currentType ? 'is-active' : '' }}" role="menuitem">All types</a>
                        <a href="{{ $typeHref('pdf') }}" class="doc-sort-subitem {{ $currentType === 'pdf' ? 'is-active' : '' }}" role="menuitem">PDF</a>
                        <a href="{{ $typeHref('word') }}" class="doc-sort-subitem {{ $currentType === 'word' ? 'is-active' : '' }}" role="menuitem">Word</a>
                    </div>
                </div>

                <div class="doc-sort-item has-submenu" role="none">
                    <span class="doc-sort-item-row {{ in_array($currentSort, ['size', 'author', 'category', 'title'], true) || request()->filled('academic_year') ? 'is-active' : '' }}">
                        @if(in_array($currentSort, ['size', 'author', 'category', 'title'], true) || request()->filled('academic_year'))<span class="doc-sort-dot" aria-hidden="true"></span>@endif
                        <span>More</span>
                        <i class="fas fa-chevron-right doc-sort-submenu-chevron" aria-hidden="true"></i>
                    </span>
                    <div class="doc-sort-submenu" role="menu">
                        <a href="{{ $sortHref('size') }}" class="doc-sort-subitem {{ $currentSort === 'size' ? 'is-active' : '' }}" role="menuitem">Size</a>
                        <a href="{{ $sortHref('author') }}" class="doc-sort-subitem {{ $currentSort === 'author' ? 'is-active' : '' }}" role="menuitem">Authors</a>
                        <a href="{{ $sortHref('category') }}" class="doc-sort-subitem {{ $currentSort === 'category' ? 'is-active' : '' }}" role="menuitem">Categories</a>
                        <a href="{{ $sortHref('title') }}" class="doc-sort-subitem {{ $currentSort === 'title' ? 'is-active' : '' }}" role="menuitem">Title</a>
                        @if($showSchoolYearFilter)
                        <div class="doc-sort-submenu-divider" role="separator"></div>
                        <span class="doc-sort-submenu-label">School year</span>
                        <a href="{{ $yearHref('') }}" class="doc-sort-subitem {{ !request('academic_year') ? 'is-active' : '' }}" role="menuitem">All years</a>
                        @foreach(AcademicYear::options() as $startYear => $label)
                        <a href="{{ $yearHref((string) $startYear) }}" class="doc-sort-subitem {{ (string) request('academic_year') === (string) $startYear ? 'is-active' : '' }}" role="menuitem">{{ $label }}</a>
                        @endforeach
                        @endif
                    </div>
                </div>

                @if($hasActiveFilters)
                <div class="doc-sort-menu-footer" role="none">
                    <a href="{{ $resetHref }}" class="doc-sort-reset" role="menuitem">
                        <i class="fas fa-rotate-left" aria-hidden="true"></i> Reset sort &amp; type
                    </a>
                </div>
                @endif
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

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        var isHidden = menu.classList.toggle('hidden');
        btn.setAttribute('aria-expanded', isHidden ? 'false' : 'true');
    });

    document.addEventListener('click', function (e) {
        if (!wrap.contains(e.target)) {
            menu.classList.add('hidden');
            btn.setAttribute('aria-expanded', 'false');
        }
    });

    menu.querySelectorAll('.has-submenu').forEach(function (item) {
        item.addEventListener('mouseenter', function () {
            menu.querySelectorAll('.has-submenu').forEach(function (el) {
                if (el !== item) el.classList.remove('submenu-open');
            });
            item.classList.add('submenu-open');
        });
    });
})();
</script>
@endpush
