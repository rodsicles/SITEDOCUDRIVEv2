@php
    $routePrefix = $routePrefix ?? 'faculty';
    $user = auth()->user();
    $documentService = app(\App\Services\DocumentService::class);
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
            $showMoreMenu = $canRename || $canDelete;
        @endphp
        <tr>
            <td>
                <div class="w-9 h-9 flex items-center justify-center text-lg bg-gray-100 dark:bg-gray-700 documents-icon">
                    @if($extension === 'pdf')
                        <i class="fas fa-file-pdf text-red-700"></i>
                    @elseif(in_array($extension, ['doc', 'docx']))
                        <i class="fas fa-file-word text-blue-700"></i>
                    @elseif(in_array($extension, ['png', 'jpg', 'jpeg']))
                        <i class="fas fa-file-image text-blue-700"></i>
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
            <td>{{ $document->uploader->employee->full_name ?? $document->uploader->username }}</td>
            <td>{{ $document->created_at->format('M d, Y') }}</td>
            <td class="doc-action-cell">
                <div class="doc-action-btns doc-action-btns--with-menu">
                    @include('partials.archive-row-actions', [
                        'viewUrl' => route($routePrefix . '.view-document', $document->document_id),
                        'downloadUrl' => route($routePrefix . '.download-document', $document->document_id),
                        'viewLabel' => 'View ' . $document->document_title,
                        'downloadLabel' => 'Download ' . $document->document_title,
                    ])
                    @if($showMoreMenu)
                    <div class="doc-action-wrap">
                        <button type="button"
                                class="doc-actions-btn"
                                data-doc-id="{{ $document->document_id }}"
                                aria-label="More actions for {{ $document->document_title }}"
                                aria-expanded="false"
                                aria-haspopup="true"
                                aria-controls="doc-popover-{{ $document->document_id }}">
                            <i class="fas fa-ellipsis-v" aria-hidden="true"></i>
                        </button>
                        <div id="doc-popover-{{ $document->document_id }}"
                             class="doc-list-popover"
                             data-popover-id="{{ $document->document_id }}"
                             role="menu"
                             hidden>
                            @if($canRename)
                            <button type="button"
                                    class="doc-list-popover-item"
                                    role="menuitem"
                                    onclick="openRenameDocumentModal({{ $document->document_id }}, @js($document->document_title))">
                                <i class="fas fa-pen text-xs" aria-hidden="true"></i> Rename
                            </button>
                            @endif
                            @if($canDelete)
                            <form id="delete-doc-{{ $document->document_id }}" action="{{ route($routePrefix . '.delete-document', $document->document_id) }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="button" class="doc-list-popover-item doc-list-popover-item--danger" role="menuitem" onclick="confirmDelete({{ $document->document_id }})">
                                    <i class="fas fa-trash text-xs" aria-hidden="true"></i> Delete
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="text-center text-gray-500 dark:text-gray-400 py-8">
                No documents available
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
