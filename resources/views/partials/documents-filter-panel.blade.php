@php
    use App\Support\DocumentListSortUrls;

    $documentsRoute = $documentsRoute ?? 'faculty.documents';
    $folderFilter = $folderFilter ?? null;
    $tab = $tab ?? request('tab', 'accreditation');
    $savedFilters = $savedFilters ?? collect();

    $currentSort = request('sort', 'date');
    $currentType = request('file_type', '');

    $hasActiveFilters = $currentType !== ''
        || ($currentSort !== '' && $currentSort !== 'date');

    $sortButtonLabel = 'Sort';
    if ($currentType === 'pdf') {
        $sortButtonLabel = 'Sort · PDF';
    } elseif ($currentType === 'word') {
        $sortButtonLabel = 'Sort · Word';
    } elseif ($currentSort === 'title') {
        $sortButtonLabel = 'Sort · Name';
    } elseif ($currentSort !== 'date' && $currentSort !== '') {
        $sortButtonLabel = 'Sort · '.ucfirst($currentSort);
    }

    $req = request();
    $currentSearch = request('search', request('name', ''));
    $searchPreserve = array_filter([
        'folder' => $folderFilter,
        'tab' => $tab,
        'sort' => request('sort'),
        'file_type' => request('file_type'),
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
                <label class="sr-only" for="docListSearchInput">Search documents</label>
                <input type="search"
                       id="docListSearchInput"
                       name="search"
                       value="{{ $currentSearch }}"
                       class="doc-search-input"
                       placeholder="Search..."
                       autocomplete="off"
                       maxlength="80">
                <button type="submit" class="doc-search-submit" title="Search" aria-label="Search documents">
                    <i class="fas fa-search" aria-hidden="true"></i>
                </button>
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

            <div class="doc-sort-menu" id="docSortMenu" role="menu" aria-label="Sort options" hidden>
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
                    <button type="button"
                            class="doc-sort-item-row {{ $currentType ? 'is-active' : '' }}"
                            id="docSortTypeBtn"
                            aria-haspopup="true"
                            aria-expanded="false"
                            aria-controls="docSortTypeFlyout">
                        @if($currentType)<span class="doc-sort-dot" aria-hidden="true"></span>@endif
                        <span>Type</span>
                        <i class="fas fa-chevron-right doc-sort-submenu-chevron" aria-hidden="true"></i>
                    </button>
                    <div class="doc-sort-flyout" id="docSortTypeFlyout" role="menu" hidden>
                        <a href="{{ DocumentListSortUrls::typeHref($documentsRoute, $req, $folderFilter, $tab, null) }}"
                           class="doc-sort-flyout-item {{ !$currentType ? 'is-active' : '' }}"
                           role="menuitem">All types</a>
                        <a href="{{ DocumentListSortUrls::typeHref($documentsRoute, $req, $folderFilter, $tab, 'pdf') }}"
                           class="doc-sort-flyout-item {{ $currentType === 'pdf' ? 'is-active' : '' }}"
                           role="menuitem">PDF</a>
                        <a href="{{ DocumentListSortUrls::typeHref($documentsRoute, $req, $folderFilter, $tab, 'word') }}"
                           class="doc-sort-flyout-item {{ $currentType === 'word' ? 'is-active' : '' }}"
                           role="menuitem">Word</a>
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
    var typeBtn = document.getElementById('docSortTypeBtn');
    var typeFlyout = document.getElementById('docSortTypeFlyout');
    if (!wrap || !btn || !menu) return;

    function closeTypeFlyout() {
        if (!typeFlyout || !typeBtn) return;
        typeFlyout.hidden = true;
        typeBtn.setAttribute('aria-expanded', 'false');
        typeBtn.closest('.has-submenu')?.classList.remove('submenu-open');
    }

    function closeMenu() {
        menu.hidden = true;
        btn.setAttribute('aria-expanded', 'false');
        closeTypeFlyout();
    }

    closeMenu();

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        if (menu.hidden) {
            menu.hidden = false;
            btn.setAttribute('aria-expanded', 'true');
        } else {
            closeMenu();
        }
    });

    if (typeBtn && typeFlyout) {
        typeBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (typeFlyout.hidden) {
                typeFlyout.hidden = false;
                typeBtn.setAttribute('aria-expanded', 'true');
                typeBtn.closest('.has-submenu')?.classList.add('submenu-open');
            } else {
                closeTypeFlyout();
            }
        });
    }

    document.addEventListener('click', function (e) {
        if (!wrap.contains(e.target)) {
            closeMenu();
        }
    });
})();
</script>
@endpush
