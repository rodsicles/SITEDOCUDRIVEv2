@php
    use App\Support\DocumentListSortUrls;

    $documentsRoute = $documentsRoute ?? 'faculty.documents';
    $folderFilter = $folderFilter ?? null;
    $tab = $tab ?? request('tab', 'accreditation');
    $savedFilters = $savedFilters ?? collect();
    $uploaders = $uploaders ?? collect();

    $currentSort = request('sort', 'date');
    $currentType = request('file_type', '');
    $currentUploader = request('uploaded_by', '');
    $currentDateFrom = request('date_from', '');
    $currentDateTo = request('date_to', '');

    $hasAdvancedFilters = $currentUploader !== '' || $currentDateFrom !== '' || $currentDateTo !== '';

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
        'uploaded_by' => $currentUploader,
        'date_from' => $currentDateFrom,
        'date_to' => $currentDateTo,
    ], fn ($v) => $v !== null && $v !== '');

    $documentsListSearchRoute = $documentsListSearchRoute ?? (
        str_ends_with($documentsRoute, '.documents')
            ? substr($documentsRoute, 0, -strlen('.documents')).'.documents.list-search'
            : $documentsRoute.'.list-search'
    );

    $clearAdvancedParams = array_filter([
        'folder' => $folderFilter,
        'tab' => $tab,
        'search' => $currentSearch !== '' ? $currentSearch : null,
        'sort' => request('sort'),
        'file_type' => request('file_type'),
    ], fn ($v) => $v !== null && $v !== '');
@endphp

<div class="card-header card-header--documents">
    <div class="card-header-documents-left">
        <h3 class="card-title mb-0">Available Documents</h3>

        <div class="doc-header-toolbar">
            <div class="doc-search-wrap" id="docListSearchWrap">
                <form action="{{ route($documentsRoute) }}" method="GET" class="doc-search-form" id="docListSearchForm" role="search">
                    @foreach($searchPreserve as $key => $value)
                        @if(!in_array($key, ['uploaded_by', 'date_from', 'date_to'], true))
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    @if($currentUploader !== '')
                        <input type="hidden" name="uploaded_by" value="{{ $currentUploader }}">
                    @endif
                    @if($currentDateFrom !== '')
                        <input type="hidden" name="date_from" value="{{ $currentDateFrom }}">
                    @endif
                    @if($currentDateTo !== '')
                        <input type="hidden" name="date_to" value="{{ $currentDateTo }}">
                    @endif
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
                    <a href="{{ route($documentsRoute, array_filter([
                        'folder' => $folderFilter,
                        'tab' => $tab,
                        'sort' => request('sort'),
                        'file_type' => request('file_type'),
                        'uploaded_by' => $currentUploader ?: null,
                        'date_from' => $currentDateFrom ?: null,
                        'date_to' => $currentDateTo ?: null,
                    ])) }}"
                       class="doc-search-clear"
                       aria-label="Clear search"
                       title="Clear search">
                        <i class="fas fa-times" aria-hidden="true"></i>
                    </a>
                    @endif
                </form>
                <div id="docListSearchSuggest" class="doc-search-suggest" role="listbox" hidden></div>
            </div>

            <button type="button"
                    class="doc-sort-trigger {{ $hasAdvancedFilters ? 'is-active' : '' }}"
                    id="docFilterToggle"
                    aria-expanded="{{ $hasAdvancedFilters ? 'true' : 'false' }}"
                    aria-controls="docAdvancedFilters">
                <i class="fas fa-filter" aria-hidden="true"></i>
                <span>Filters</span>
            </button>

            <div class="doc-sort-wrap" id="docSortWrap">
            <button type="button"
                    class="doc-sort-trigger {{ ($currentType !== '' || ($currentSort !== '' && $currentSort !== 'date')) ? 'is-active' : '' }}"
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

                @if($currentType !== '' || ($currentSort !== '' && $currentSort !== 'date'))
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

<div id="docAdvancedFilters" class="doc-advanced-filters {{ $hasAdvancedFilters ? '' : 'is-collapsed' }}">
    <form action="{{ route($documentsRoute) }}" method="GET" class="doc-advanced-filters__form">
        <input type="hidden" name="tab" value="{{ $tab }}">
        @if($folderFilter)
            <input type="hidden" name="folder" value="{{ $folderFilter }}">
        @endif
        @if($currentSearch !== '')
            <input type="hidden" name="search" value="{{ $currentSearch }}">
        @endif
        @if(request('sort'))
            <input type="hidden" name="sort" value="{{ request('sort') }}">
        @endif
        @if(request('file_type'))
            <input type="hidden" name="file_type" value="{{ request('file_type') }}">
        @endif

        <div class="doc-advanced-filters__field">
            <label class="doc-advanced-filters__label" for="docFilterUploader">Uploaded by</label>
            <select name="uploaded_by" id="docFilterUploader" class="form-control doc-advanced-filters__control">
                <option value="">Anyone</option>
                @foreach($uploaders as $uploader)
                    <option value="{{ $uploader->id }}" @selected((string) $currentUploader === (string) $uploader->id)>
                        {{ $uploader->employee->full_name ?? $uploader->username }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="doc-advanced-filters__field">
            <label class="doc-advanced-filters__label" for="docFilterDateFrom">From</label>
            <input type="date" name="date_from" id="docFilterDateFrom" value="{{ $currentDateFrom }}" class="form-control doc-advanced-filters__control">
        </div>

        <div class="doc-advanced-filters__field">
            <label class="doc-advanced-filters__label" for="docFilterDateTo">To</label>
            <input type="date" name="date_to" id="docFilterDateTo" value="{{ $currentDateTo }}" class="form-control doc-advanced-filters__control">
        </div>

        <div class="doc-advanced-filters__actions">
            <button type="submit" class="btn btn-primary text-xs">
                <i class="fas fa-check mr-1" aria-hidden="true"></i> Apply
            </button>
            @if($hasAdvancedFilters)
            <a href="{{ route($documentsRoute, $clearAdvancedParams) }}" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs">
                Clear
            </a>
            @endif
        </div>
    </form>

    @if($hasAdvancedFilters || $currentSearch !== '' || $currentType !== '')
    <form action="{{ route('document-filters.store') }}" method="POST" class="doc-save-filter-form">
        @csrf
        <input type="hidden" name="tab" value="{{ $tab }}">
        @if($folderFilter)<input type="hidden" name="folder" value="{{ $folderFilter }}">@endif
        @if($currentSearch !== '')<input type="hidden" name="search" value="{{ $currentSearch }}">@endif
        @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
        @if(request('file_type'))<input type="hidden" name="file_type" value="{{ request('file_type') }}">@endif
        @if($currentUploader !== '')<input type="hidden" name="uploaded_by" value="{{ $currentUploader }}">@endif
        @if($currentDateFrom !== '')<input type="hidden" name="date_from" value="{{ $currentDateFrom }}">@endif
        @if($currentDateTo !== '')<input type="hidden" name="date_to" value="{{ $currentDateTo }}">@endif
        <input type="text" name="name" class="form-control doc-save-filter-form__name" placeholder="Save filter as…" maxlength="50" required>
        <button type="submit" class="btn btn-sm btn-success border-0">
            <i class="fas fa-bookmark mr-1" aria-hidden="true"></i> Save
        </button>
    </form>
    @endif
</div>

@if($hasAdvancedFilters || $savedFilters->isNotEmpty())
<div class="doc-saved-presets px-4 pb-2">
    @if($currentUploader !== '')
        @php
            $uploaderUser = $uploaders->firstWhere('id', (int) $currentUploader);
            $uploaderLabel = optional(optional($uploaderUser)->employee)->full_name
                ?? optional($uploaderUser)->username
                ?? 'Uploader';
        @endphp
        <span class="doc-saved-preset-chip is-active">By: {{ $uploaderLabel }}</span>
    @endif
    @if($currentDateFrom !== '' || $currentDateTo !== '')
        <span class="doc-saved-preset-chip is-active">
            Dates: {{ $currentDateFrom !== '' ? $currentDateFrom : '…' }} → {{ $currentDateTo !== '' ? $currentDateTo : '…' }}
        </span>
    @endif
    @foreach($savedFilters as $savedFilter)
    <span class="doc-saved-preset-chip-wrap">
        <a href="{{ route($documentsRoute, array_merge($savedFilter->toQueryParams(), ['saved_filter' => $savedFilter->document_filter_id, 'folder' => $folderFilter, 'tab' => $tab])) }}"
           class="doc-saved-preset-chip">{{ $savedFilter->name }}</a>
        <form action="{{ route('document-filters.destroy', $savedFilter->document_filter_id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this saved filter?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="doc-saved-preset-remove" title="Delete saved filter" aria-label="Delete {{ $savedFilter->name }}">&times;</button>
        </form>
    </span>
    @endforeach
</div>
@endif

@push('scripts')
<script>
(function () {
    var toggle = document.getElementById('docFilterToggle');
    var panel = document.getElementById('docAdvancedFilters');
    if (toggle && panel) {
        toggle.addEventListener('click', function () {
            var collapsed = panel.classList.toggle('is-collapsed');
            toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        });
    }
})();

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
    if (params.has('sort') || params.has('file_type') || params.has('uploaded_by') || params.has('date_from') || params.has('date_to')) {
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
