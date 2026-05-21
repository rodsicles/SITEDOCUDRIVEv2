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
        'category' => request('category'),
    ], fn ($v) => $v !== null && $v !== '');

    $documentsListSearchRoute = $documentsListSearchRoute ?? (
        str_ends_with($documentsRoute, '.documents')
            ? substr($documentsRoute, 0, -strlen('.documents')).'.documents.list-search'
            : $documentsRoute.'.list-search'
    );
@endphp

<div class="card-header card-header--documents">
    <div class="card-header-documents-left">
        <h3 class="card-title mb-0">Available Documents</h3>

        <div class="doc-header-toolbar">
            <div class="doc-search-wrap" id="docListSearchWrap">
                <form action="{{ route($documentsRoute) }}" method="GET" class="doc-search-form" id="docListSearchForm" role="search">
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
                           maxlength="80"
                           aria-autocomplete="list"
                           aria-controls="docListSearchSuggest"
                           aria-expanded="false">
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
                <div id="docListSearchSuggest" class="doc-search-suggest" role="listbox" hidden></div>
            </div>

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

(function () {
    var params = new URLSearchParams(window.location.search);
    if (params.has('sort') || params.has('file_type')) {
        var table = document.getElementById('documentsListTable');
        if (table) {
            setTimeout(function () {
                table.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 80);
        }
    }
})();

(function () {
    var searchWrap = document.getElementById('docListSearchWrap');
    var searchInput = document.getElementById('docListSearchInput');
    var searchForm = document.getElementById('docListSearchForm');
    var suggestBox = document.getElementById('docListSearchSuggest');
    if (!searchWrap || !searchInput || !searchForm || !suggestBox) return;

    var searchUrl = @json(route($documentsListSearchRoute));
    var debounceTimer = null;
    var activeIndex = -1;

    function hideSuggest() {
        suggestBox.hidden = true;
        suggestBox.innerHTML = '';
        searchInput.setAttribute('aria-expanded', 'false');
        activeIndex = -1;
    }

    function applySuggestion(title) {
        searchInput.value = title;
        hideSuggest();
        searchForm.requestSubmit();
    }

    function renderSuggestions(items) {
        suggestBox.innerHTML = '';
        if (!items.length) {
            hideSuggest();
            return;
        }
        items.forEach(function (item, index) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'doc-search-suggest-item';
            btn.setAttribute('role', 'option');
            btn.textContent = item.title;
            btn.addEventListener('click', function () {
                applySuggestion(item.title);
            });
            btn.addEventListener('mouseenter', function () {
                activeIndex = index;
                highlightActive();
            });
            suggestBox.appendChild(btn);
        });
        suggestBox.hidden = false;
        searchInput.setAttribute('aria-expanded', 'true');
        activeIndex = -1;
    }

    function highlightActive() {
        var buttons = suggestBox.querySelectorAll('.doc-search-suggest-item');
        buttons.forEach(function (btn, i) {
            btn.classList.toggle('is-active', i === activeIndex);
        });
    }

    async function fetchSuggestions() {
        var q = searchInput.value.trim();
        if (q.length < 3) {
            hideSuggest();
            return;
        }
        var url = new URL(searchUrl, window.location.origin);
        url.searchParams.set('q', q);
        var params = new URLSearchParams(new FormData(searchForm));
        params.forEach(function (value, key) {
            if (key !== 'search') {
                url.searchParams.set(key, value);
            }
        });
        try {
            var res = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) {
                hideSuggest();
                return;
            }
            var data = await res.json();
            renderSuggestions(data.results || []);
        } catch (e) {
            hideSuggest();
        }
    }

    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(fetchSuggestions, 280);
    });

    searchInput.addEventListener('keydown', function (e) {
        var buttons = suggestBox.querySelectorAll('.doc-search-suggest-item');
        if (e.key === 'ArrowDown' && !suggestBox.hidden && buttons.length) {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, buttons.length - 1);
            highlightActive();
        } else if (e.key === 'ArrowUp' && !suggestBox.hidden && buttons.length) {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
            highlightActive();
        } else if (e.key === 'Enter' && activeIndex >= 0 && buttons[activeIndex]) {
            e.preventDefault();
            applySuggestion(buttons[activeIndex].textContent);
        } else if (e.key === 'Escape') {
            hideSuggest();
        }
    });

    document.addEventListener('click', function (e) {
        if (!searchWrap.contains(e.target)) {
            hideSuggest();
        }
    });
})();
</script>
@endpush
