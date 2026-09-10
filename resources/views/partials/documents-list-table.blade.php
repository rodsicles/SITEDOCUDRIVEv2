@php
    $routePrefix = $routePrefix ?? 'faculty';
    $user = auth()->user();
    $documentService = app(\App\Services\DocumentService::class);
    $foldersListUrl = route($routePrefix . '.folders.list');
    $favoriteRoute = $routePrefix . '.toggle-favorite';
    $canFavorite = \Illuminate\Support\Facades\Route::has($favoriteRoute);
    $docsListRoute = $routePrefix . '.documents';
    $hasListFilters = request()->filled('search')
        || request()->filled('uploaded_by')
        || request()->filled('date_from')
        || request()->filled('date_to')
        || request()->filled('file_type');
@endphp

<div class="documents-list-table-wrap">
<table class="data-table" id="documentsListTable">
    <thead>
        <tr>
            <th class="w-12"></th>
            <th>Document Title</th>
            <th>Type</th>
            <th>Uploaded By</th>
            <th>Upload Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($documents as $document)
        @php
            $extension = strtolower(pathinfo($document->file_path, PATHINFO_EXTENSION));
            $canRename = $documentService->userCanRenameDocument($document, $user);
            $canDelete = $user->isDean() || $user->isSecretary() || (int) $document->uploaded_by === (int) $user->id;
            $canCopy   = $document->canView($user);
            $isFavorited = $canFavorite && $document->isFavoritedBy($user->id);
        @endphp
        <tr>
            <td>
                <div class="w-9 h-9 flex items-center justify-center text-lg bg-gray-100 dark:bg-gray-700 documents-icon">
                    @if($extension === 'pdf')
                        <i class="fas fa-file-pdf text-red-700"></i>
                    @elseif(in_array($extension, ['doc', 'docx']))
                        <i class="fas fa-file-word text-blue-700"></i>
                    @elseif(in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp']))
                        <i class="fas fa-file-image text-green-700"></i>
                    @else
                        <i class="fas fa-file text-gray-600"></i>
                    @endif
                </div>
            </td>
            <td>
                <strong class="doc-title-text" id="doc-title-text-{{ $document->document_id }}">{{ $document->document_title }}</strong>
            </td>
            <td>
                @if($document->category)
                    <span class="doc-category-badge">{{ $document->category }}</span>
                @elseif($document->document_type === 'pdf')
                    <span class="doc-category-badge">PDF Document</span>
                @elseif($document->document_type === 'word')
                    <span class="doc-category-badge">Word Document</span>
                @elseif($document->document_type === 'image')
                    <span class="doc-category-badge">Image File</span>
                @else
                    <span class="doc-category-badge">{{ $document->document_type ?? 'General' }}</span>
                @endif
            </td>
            <td>{{ $document->uploader ? ($document->uploader->employee->full_name ?? $document->uploader->username) : 'Unknown' }}</td>
            <td>{{ $document->created_at->format('M d, Y g:i A') }}</td>
            <td class="doc-action-cell">
                <div class="doc-action-btns archive-row-actions" role="group" aria-label="Document actions">
                    @if($canFavorite)
                    <button type="button"
                            class="btn btn-sm border-0 archive-row-actions__btn doc-favorite-btn {{ $isFavorited ? 'is-favorited' : '' }}"
                            title="{{ $isFavorited ? 'Remove from favorites' : 'Add to favorites' }}"
                            aria-label="{{ $isFavorited ? 'Unfavorite' : 'Favorite' }} {{ $document->document_title }}"
                            data-favorite-url="{{ route($favoriteRoute, $document->document_id) }}"
                            data-favorited="{{ $isFavorited ? '1' : '0' }}">
                        <i class="{{ $isFavorited ? 'fas' : 'far' }} fa-star" aria-hidden="true"></i>
                    </button>
                    @endif
                    @include('partials.archive-row-actions', [
                        'viewUrl' => route($routePrefix . '.view-document', $document->document_id),
                        'downloadUrl' => route($routePrefix . '.download-document', $document->document_id),
                        'viewLabel' => 'View ' . $document->document_title,
                        'downloadLabel' => 'Download ' . $document->document_title,
                    ])
                    @if($canRename)
                    <button type="button"
                            class="btn btn-sm btn-success border-0 archive-row-actions__btn"
                            title="Rename"
                            aria-label="Rename {{ $document->document_title }}"
                            onclick="openRenameDocumentModal({{ $document->document_id }}, @js($document->document_title))">
                        <i class="fas fa-pen" aria-hidden="true"></i>
                    </button>
                    @endif
                    @if($canCopy)
                    <button type="button"
                            class="btn btn-sm btn-success border-0 archive-row-actions__btn"
                            title="Copy to…"
                            aria-label="Copy {{ $document->document_title }}"
                            onclick="openCopyDocumentModal({{ $document->document_id }}, @js(route($routePrefix.'.documents.copy', $document->document_id)))">
                        <i class="fas fa-copy" aria-hidden="true"></i>
                    </button>
                    @endif
                    @if($canDelete)
                    <form id="delete-doc-{{ $document->document_id }}"
                          action="{{ route($routePrefix . '.delete-document', $document->document_id) }}"
                          method="POST"
                          class="submission-review-actions__form">
                        @csrf @method('DELETE')
                        <button type="button"
                                class="btn btn-sm btn-danger border-0 archive-row-actions__btn"
                                title="Delete"
                                aria-label="Delete {{ $document->document_title }}"
                                onclick="confirmDelete({{ $document->document_id }})">
                            <i class="fas fa-trash" aria-hidden="true"></i>
                        </button>
                    </form>
                    @endif
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="py-10">
                <div class="docs-empty-state">
                    <div class="docs-empty-state__icon"><i class="fas fa-folder-open" aria-hidden="true"></i></div>
                    <p class="docs-empty-state__title">No documents in this view</p>
                    @if($hasListFilters)
                        <p class="docs-empty-state__text">Nothing matches your current filters or search.</p>
                        <a href="{{ route($docsListRoute, array_filter(['tab' => request('tab', 'accreditation'), 'folder' => request('folder')])) }}"
                           class="btn btn-primary text-sm mt-3">
                            <i class="fas fa-rotate-left mr-1" aria-hidden="true"></i> Clear filters
                        </a>
                    @elseif(request('folder'))
                        <p class="docs-empty-state__text">This folder is empty. Upload a file here, or open another folder above.</p>
                    @else
                        <p class="docs-empty-state__text">Open a folder above to browse files, or upload once you are inside a folder.</p>
                    @endif
                </div>
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>

<div class="mt-5">
    {{ $documents->links() }}
</div>

@include('partials.rename-document-modal', ['routePrefix' => $routePrefix])

{{-- ── Copy Document Modal ──────────────────────────────────────────────── --}}
<div id="copyDocumentModalBackdrop" class="copy-doc-modal-backdrop" aria-modal="true" role="dialog">
    <div class="copy-doc-modal">
        <h4><i class="fas fa-copy mr-2 text-[#028a0f]"></i>Copy Document To…</h4>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Choose a destination folder. The file will be duplicated there.</p>
        <select id="copyDocumentFolderSelect" class="form-control">
            <option value="">— Root Documents (no folder) —</option>
        </select>
        <div class="flex gap-3">
            <button type="button" id="copyDocumentConfirmBtn" class="btn btn-primary text-xs">
                <i class="fas fa-copy mr-1"></i> Copy
            </button>
            <button type="button" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs"
                    onclick="closeCopyDocumentModal()">Cancel</button>
        </div>
        <p id="copyDocumentStatus" class="text-xs mt-2 hidden"></p>
    </div>
</div>

@once
@push('scripts')
<script>
function confirmDelete(id) {
    if (typeof Swal === 'undefined') {
        if (confirm('Move this file to the Recycle Bin?')) {
            document.getElementById('delete-doc-' + id).submit();
        }
        return;
    }

    Swal.fire({
        title: 'Move to Recycle Bin?',
        text: 'This file will be removed from Documents and moved to the Recycle Bin. You can restore it later.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        customClass: { popup: 'swal-flat' }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('delete-doc-' + id).submit();
        }
    });
}

document.addEventListener('click', function (e) {
    var btn = e.target.closest('.doc-favorite-btn');
    if (!btn) return;
    e.preventDefault();
    var url = btn.getAttribute('data-favorite-url');
    if (!url) return;
    btn.disabled = true;
    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
        if (!data || !data.success) return;
        var on = !!data.favorited;
        btn.dataset.favorited = on ? '1' : '0';
        btn.classList.toggle('is-favorited', on);
        btn.title = on ? 'Remove from favorites' : 'Add to favorites';
        var icon = btn.querySelector('i');
        if (icon) {
            icon.classList.toggle('fas', on);
            icon.classList.toggle('far', !on);
        }
        if (typeof showToast === 'function') {
            showToast(data.message || (on ? 'Added to favorites' : 'Removed from favorites'), 'success');
        }
    })
    .catch(function () {})
    .finally(function () { btn.disabled = false; });
});

// ── Copy Document Modal ─────────────────────────────────────────────────
(function () {
    var _copyUrl = '';
    var _foldersUrl = @json($foldersListUrl);

    window.openCopyDocumentModal = function (docId, copyEndpoint) {
        _copyUrl = copyEndpoint;
        var backdrop = document.getElementById('copyDocumentModalBackdrop');
        var select   = document.getElementById('copyDocumentFolderSelect');
        var status   = document.getElementById('copyDocumentStatus');
        if (!backdrop || !select) return;

        select.innerHTML = '<option value="">— Root Documents (no folder) —</option>';
        status.classList.add('hidden');
        status.textContent = '';
        backdrop.classList.add('open');

        fetch(_foldersUrl)
            .then(r => r.json())
            .then(function (data) {
                (data.folders || []).forEach(function (f) {
                    var opt = document.createElement('option');
                    opt.value = f.folder_id;
                    opt.textContent = f.folder_name;
                    select.appendChild(opt);
                });
            })
            .catch(function () {
                status.textContent = 'Could not load folders.';
                status.classList.remove('hidden');
            });
    };

    window.closeCopyDocumentModal = function () {
        var backdrop = document.getElementById('copyDocumentModalBackdrop');
        if (backdrop) backdrop.classList.remove('open');
    };

    document.addEventListener('DOMContentLoaded', function () {
        var backdrop = document.getElementById('copyDocumentModalBackdrop');
        var confirmBtn = document.getElementById('copyDocumentConfirmBtn');
        var status = document.getElementById('copyDocumentStatus');

        if (confirmBtn) {
            confirmBtn.addEventListener('click', function () {
                var select = document.getElementById('copyDocumentFolderSelect');
                var folderId = select ? select.value : '';
                var body = new FormData();
                body.append('_token', document.querySelector('meta[name=csrf-token]')?.content || '');
                if (folderId) body.append('folder_id', folderId);

                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Copying…';
                status.classList.add('hidden');

                fetch(_copyUrl, { method: 'POST', body: body })
                    .then(r => r.json())
                    .then(function (data) {
                        status.textContent = data.message || 'Copied successfully.';
                        status.classList.remove('hidden');
                        status.style.color = data.success ? '#028a0f' : '#dc2626';
                        if (data.success) {
                            setTimeout(function () { window.closeCopyDocumentModal(); }, 1200);
                        }
                    })
                    .catch(function () {
                        status.textContent = 'An error occurred. Please try again.';
                        status.classList.remove('hidden');
                        status.style.color = '#dc2626';
                    })
                    .finally(function () {
                        confirmBtn.disabled = false;
                        confirmBtn.innerHTML = '<i class="fas fa-copy mr-1"></i> Copy';
                    });
            });
        }

        if (backdrop) {
            backdrop.addEventListener('click', function (e) {
                if (e.target === backdrop) window.closeCopyDocumentModal();
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') window.closeCopyDocumentModal();
        });
    });
})();

document.addEventListener('DOMContentLoaded', function () {
    function closeDocPopover(popover, toggleBtn) {
        if (!popover) return;
        popover.classList.remove('is-open');
        popover.setAttribute('hidden', '');
        if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
    }

    function closeAllDocPopovers() {
        document.querySelectorAll('.doc-list-popover').forEach(function (popover) {
            var id = popover.dataset.popoverId;
            var btn = document.querySelector('.doc-actions-btn[data-doc-id="' + id + '"]');
            closeDocPopover(popover, btn);
        });
    }

    document.querySelectorAll('.doc-actions-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var id = btn.dataset.docId;
            var popover = document.getElementById('doc-popover-' + id);
            if (!popover) return;
            var isOpen = popover.classList.contains('is-open');
            closeAllDocPopovers();
            if (!isOpen) {
                popover.classList.add('is-open');
                popover.removeAttribute('hidden');
                btn.setAttribute('aria-expanded', 'true');
            }
        });
    });

    document.addEventListener('click', function () {
        closeAllDocPopovers();
    });

    document.querySelectorAll('.doc-list-popover').forEach(function (popover) {
        popover.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeAllDocPopovers();
    });
});
</script>
@endpush
@endonce
