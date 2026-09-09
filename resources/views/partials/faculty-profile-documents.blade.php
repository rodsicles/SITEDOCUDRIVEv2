{{--
  Faculty profile documents (Dean / Coordinator view):
  1) Recent uploads
  2) Autosuggest search
  3) Improved folder tree (secondary, collapsed by default)
--}}
@php
    $viewRoute = $viewRoute ?? (auth()->user()->isDeanOrSecretary() ? 'dean.view-document' : 'coordinator.view-document');
    $downloadRoute = $downloadRoute ?? (auth()->user()->isDeanOrSecretary() ? 'dean.download-document' : 'coordinator.download-document');
    $recentDocuments = ($documents ?? collect())->take(10);
    $searchIndex = ($documents ?? collect())->map(function ($doc) use ($viewRoute, $downloadRoute) {
        return [
            'id' => $doc->document_id,
            'title' => $doc->document_title,
            'category' => $doc->category ?: ($doc->folder?->top_level_category ?? 'Other'),
            'subject' => $doc->subject ?? '',
            'type' => $doc->document_type ?? '',
            'folder' => $doc->folder?->folder_name ?? 'Uncategorized',
            'date' => optional($doc->created_at)->format('M d, Y g:i A'),
            'view' => route($viewRoute, $doc->document_id),
            'download' => route($downloadRoute, $doc->document_id),
        ];
    })->values();
@endphp

<div class="faculty-profile-docs" id="facultyProfileDocs">
    {{-- Category summary chips --}}
    <div class="faculty-profile-docs__stats">
        @foreach(($documentStats['byCategory'] ?? []) as $category => $count)
            <div class="faculty-profile-docs__stat">
                <i class="fas fa-folder" aria-hidden="true"></i>
                <span>{{ $category }}</span>
                <strong>{{ $count }}</strong>
            </div>
        @endforeach
    </div>

    {{-- 1. Recent uploads --}}
    <div class="faculty-profile-docs__section">
        <div class="faculty-profile-docs__section-head">
            <h4 class="faculty-profile-docs__section-title">
                <i class="fas fa-clock mr-1 text-[#028a0f]"></i> Recent Uploads
            </h4>
            <span class="text-xs text-gray-500 dark:text-gray-400">Latest {{ $recentDocuments->count() }} of {{ $documentStats['total'] ?? 0 }}</span>
        </div>
        <div class="faculty-profile-docs__recent">
            @foreach($recentDocuments as $doc)
                @php $ext = strtolower(pathinfo($doc->file_path, PATHINFO_EXTENSION)); @endphp
                <div class="faculty-profile-docs__recent-row">
                    <div class="faculty-profile-docs__recent-main">
                        @if($ext === 'pdf')
                            <i class="fas fa-file-pdf text-red-600"></i>
                        @elseif(in_array($ext, ['doc', 'docx'], true))
                            <i class="fas fa-file-word text-blue-600"></i>
                        @elseif(in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true))
                            <i class="fas fa-file-image text-green-700"></i>
                        @else
                            <i class="fas fa-file text-gray-500"></i>
                        @endif
                        <div class="min-w-0">
                            <div class="faculty-profile-docs__recent-title">{{ $doc->document_title }}</div>
                            <div class="faculty-profile-docs__recent-meta">
                                {{ $doc->category ?: 'Other' }}
                                @if($doc->folder)
                                    · {{ $doc->folder->folder_name }}
                                @endif
                                · {{ $doc->created_at->format('M d, Y g:i A') }}
                            </div>
                        </div>
                    </div>
                    <div class="archive-row-actions">
                        <a href="{{ route($viewRoute, $doc->document_id) }}"
                           class="btn btn-sm btn-success border-0 archive-row-actions__btn"
                           title="View"
                           aria-label="View {{ $doc->document_title }}">
                            <i class="fas fa-eye" aria-hidden="true"></i>
                        </a>
                        <a href="{{ route($downloadRoute, $doc->document_id) }}"
                           class="btn btn-sm btn-success border-0 archive-row-actions__btn"
                           title="Download"
                           aria-label="Download {{ $doc->document_title }}">
                            <i class="fas fa-download" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- 2. Search + autosuggest --}}
    <div class="faculty-profile-docs__section">
        <div class="faculty-profile-docs__section-head">
            <h4 class="faculty-profile-docs__section-title">
                <i class="fas fa-search mr-1 text-[#028a0f]"></i> Find a Document
            </h4>
        </div>
        <div class="faculty-profile-docs__search-wrap">
            <input type="search"
                   id="facultyDocSearch"
                   class="form-control faculty-profile-docs__search-input"
                   placeholder="Search by title, subject, category, or folder…"
                   autocomplete="off"
                   aria-autocomplete="list"
                   aria-controls="facultyDocSuggest"
                   aria-expanded="false">
            <div id="facultyDocSuggest" class="faculty-profile-docs__suggest hidden" role="listbox"></div>
        </div>
        <div id="facultyDocSearchResults" class="faculty-profile-docs__search-results hidden"></div>
        <p id="facultyDocSearchEmpty" class="faculty-profile-docs__search-empty hidden">No documents match your search.</p>
    </div>

    {{-- 3. Folder tree (secondary) --}}
    <div class="faculty-profile-docs__section">
        <div class="faculty-profile-docs__section-head">
            <h4 class="faculty-profile-docs__section-title">
                <i class="fas fa-sitemap mr-1 text-[#028a0f]"></i> Browse by Folder
            </h4>
            <div class="faculty-profile-docs__tree-tools">
                <button type="button" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs" id="facultyDocTreeExpand">
                    Expand all
                </button>
                <button type="button" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs" id="facultyDocTreeCollapse">
                    Collapse all
                </button>
            </div>
        </div>
        @include('partials.faculty-document-tree', [
            'documentTree' => $documentTree ?? [],
            'viewRoute' => $viewRoute,
            'downloadRoute' => $downloadRoute,
        ])
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    const docs = @json($searchIndex);
    const root = document.getElementById('facultyProfileDocs');
    if (!root) return;

    const searchInput = document.getElementById('facultyDocSearch');
    const suggestBox = document.getElementById('facultyDocSuggest');
    const resultsBox = document.getElementById('facultyDocSearchResults');
    const emptyMsg = document.getElementById('facultyDocSearchEmpty');
    const treeRoot = root.querySelector('.faculty-doc-tree');

    function escapeHtml(str) {
        const d = document.createElement('div');
        d.textContent = str ?? '';
        return d.innerHTML;
    }

    function matches(doc, q) {
        if (!q) return true;
        const hay = [doc.title, doc.category, doc.subject, doc.folder, doc.type]
            .join(' ')
            .toLowerCase();
        return hay.includes(q);
    }

    function renderRow(doc) {
        return (
            '<div class="faculty-profile-docs__recent-row" data-doc-id="' + doc.id + '">' +
                '<div class="faculty-profile-docs__recent-main">' +
                    '<i class="fas fa-file text-gray-500"></i>' +
                    '<div class="min-w-0">' +
                        '<div class="faculty-profile-docs__recent-title">' + escapeHtml(doc.title) + '</div>' +
                        '<div class="faculty-profile-docs__recent-meta">' +
                            escapeHtml(doc.category || 'Other') +
                            (doc.folder ? ' · ' + escapeHtml(doc.folder) : '') +
                            (doc.date ? ' · ' + escapeHtml(doc.date) : '') +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="archive-row-actions">' +
                    '<a href="' + escapeHtml(doc.view) + '" class="btn btn-sm btn-success border-0 archive-row-actions__btn" title="View"><i class="fas fa-eye"></i></a>' +
                    '<a href="' + escapeHtml(doc.download) + '" class="btn btn-sm btn-success border-0 archive-row-actions__btn" title="Download"><i class="fas fa-download"></i></a>' +
                '</div>' +
            '</div>'
        );
    }

    function filterTree(q) {
        if (!treeRoot) return;
        const items = treeRoot.querySelectorAll('.doc-tree-file-item');
        let anyVisible = false;

        items.forEach(function (item) {
            const text = (item.getAttribute('data-search') || '').toLowerCase();
            const show = !q || text.includes(q);
            item.classList.toggle('doc-tree-hidden', !show);
            if (show) anyVisible = true;
            if (show && q) {
                let el = item.parentElement;
                while (el && el !== treeRoot) {
                    if (el.tagName === 'DETAILS') el.open = true;
                    el = el.parentElement;
                }
            }
        });

        treeRoot.querySelectorAll('.doc-tree-node, .doc-tree-folder, .doc-tree-category').forEach(function (details) {
            if (!q) return;
            const visibleChild = details.querySelector('.doc-tree-file-item:not(.doc-tree-hidden)');
            details.classList.toggle('doc-tree-hidden', !visibleChild);
        });

        if (!q) {
            treeRoot.querySelectorAll('.doc-tree-hidden').forEach(function (el) {
                el.classList.remove('doc-tree-hidden');
            });
            treeRoot.querySelectorAll('details.doc-tree-category, details.doc-tree-node, details.doc-tree-folder').forEach(function (d) {
                d.open = false;
            });
        }
    }

    function runSearch(raw) {
        const q = (raw || '').trim().toLowerCase();
        const hits = docs.filter(function (d) { return matches(d, q); });

        if (!q) {
            suggestBox.classList.add('hidden');
            suggestBox.innerHTML = '';
            searchInput.setAttribute('aria-expanded', 'false');
            resultsBox.classList.add('hidden');
            resultsBox.innerHTML = '';
            emptyMsg.classList.add('hidden');
            filterTree('');
            return;
        }

        // Autosuggest (top 8)
        const suggest = hits.slice(0, 8);
        if (suggest.length === 0) {
            suggestBox.classList.add('hidden');
            suggestBox.innerHTML = '';
            searchInput.setAttribute('aria-expanded', 'false');
        } else {
            suggestBox.innerHTML = suggest.map(function (d) {
                return '<button type="button" class="faculty-profile-docs__suggest-item" role="option" data-id="' + d.id + '">' +
                    '<strong>' + escapeHtml(d.title) + '</strong>' +
                    '<span>' + escapeHtml(d.category || '') + (d.folder ? ' · ' + escapeHtml(d.folder) : '') + '</span>' +
                '</button>';
            }).join('');
            suggestBox.classList.remove('hidden');
            searchInput.setAttribute('aria-expanded', 'true');
        }

        // Full results panel
        if (hits.length === 0) {
            resultsBox.classList.add('hidden');
            resultsBox.innerHTML = '';
            emptyMsg.classList.remove('hidden');
        } else {
            emptyMsg.classList.add('hidden');
            resultsBox.innerHTML = '<div class="faculty-profile-docs__results-label">' + hits.length + ' match' + (hits.length === 1 ? '' : 'es') + '</div>' +
                hits.map(renderRow).join('');
            resultsBox.classList.remove('hidden');
        }

        filterTree(q);
    }

    searchInput.addEventListener('input', function () {
        runSearch(this.value);
    });

    suggestBox.addEventListener('click', function (e) {
        const btn = e.target.closest('.faculty-profile-docs__suggest-item');
        if (!btn) return;
        const id = btn.getAttribute('data-id');
        const doc = docs.find(function (d) { return String(d.id) === String(id); });
        if (!doc) return;
        searchInput.value = doc.title;
        runSearch(doc.title);
        suggestBox.classList.add('hidden');
        searchInput.setAttribute('aria-expanded', 'false');
        const row = resultsBox.querySelector('[data-doc-id="' + id + '"]');
        if (row) row.scrollIntoView({ block: 'nearest' });
    });

    document.addEventListener('click', function (e) {
        if (!root.contains(e.target)) {
            suggestBox.classList.add('hidden');
            searchInput.setAttribute('aria-expanded', 'false');
        }
    });

    document.getElementById('facultyDocTreeExpand')?.addEventListener('click', function () {
        treeRoot?.querySelectorAll('details').forEach(function (d) { d.open = true; });
    });

    document.getElementById('facultyDocTreeCollapse')?.addEventListener('click', function () {
        treeRoot?.querySelectorAll('details').forEach(function (d) { d.open = false; });
    });
})();
</script>
@endpush
@endonce
