@php
    $routePrefix = $routePrefix ?? 'faculty';
    $canForceDelete = $canForceDelete ?? false;
@endphp

<div class="content-card recycle-bin-card">
    <div class="card-header recycle-bin-card__header">
        <div>
            <h3 class="card-title">
                <i class="fas fa-trash-restore mr-2 text-[#028a0f]"></i>Deleted Files
            </h3>
            <p class="recycle-bin-card__subtitle">
                Files removed from Documents are kept here. Restore returns them to their original folder when it still exists;
                otherwise they go to <strong>Uncategorized</strong>.
                @if($canForceDelete)
                    As Dean, you can permanently delete items — the uploader will be notified and the file cannot be recovered.
                @endif
            </p>
        </div>
        <span class="badge badge-info">{{ $documents->total() }} item(s)</span>
    </div>

    @if($canForceDelete && $documents->count() > 0)
    <form action="{{ route($routePrefix . '.recycle-bin.bulk-force-delete') }}" method="POST" class="recycle-bin-bulk-form px-4 mb-3" id="recycleBinBulkForm" data-request-guard>
        @csrf
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" class="btn bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm" id="recycleBinSelectAll">
                Select all on page
            </button>
            <button type="button" class="btn bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm" id="recycleBinClearSelection">
                Clear selection
            </button>
            <button type="button" class="btn btn-danger text-sm" id="recycleBinBulkDeleteBtn" disabled>
                <i class="fas fa-trash-alt"></i> Permanently delete selected
            </button>
            <span class="text-xs text-gray-500 dark:text-gray-400" id="recycleBinSelectedCount">0 selected</span>
        </div>
    </form>
    @endif

    <div class="recycle-bin-table-wrap">
        <table class="data-table recycle-bin-table">
            <thead>
                <tr>
                    @if($canForceDelete)
                    <th class="w-10">
                        <span class="sr-only">Select</span>
                    </th>
                    @endif
                    <th>File Name</th>
                    <th>Original Folder</th>
                    <th>Deleted By</th>
                    <th>Deleted At</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($documents as $document)
                <tr>
                    @if($canForceDelete)
                    <td>
                        <input type="checkbox" name="document_ids[]" value="{{ $document->document_id }}" form="recycleBinBulkForm" class="recycle-bin-row-check" aria-label="Select {{ $document->document_title }}">
                    </td>
                    @endif
                    <td>
                        <div class="recycle-bin-file">
                            <span class="recycle-bin-file__icon" aria-hidden="true">
                                <i class="fas fa-file-alt"></i>
                            </span>
                            <strong class="recycle-bin-file__title">{{ $document->document_title }}</strong>
                        </div>
                    </td>
                    <td class="recycle-bin-folder">{{ $document->recycle_bin_folder_label }}</td>
                    <td>{{ $document->deletedBy?->employee?->full_name ?? $document->deletedBy?->username ?? '—' }}</td>
                    <td class="whitespace-nowrap text-sm">{{ $document->deleted_at?->format('M d, Y h:i A') ?? '—' }}</td>
                    <td class="text-right">
                        <div class="recycle-bin-action-wrap">
                            <button type="button"
                                    class="recycle-bin-actions-btn"
                                    data-doc-id="{{ $document->document_id }}"
                                    aria-label="Actions for {{ $document->document_title }}"
                                    aria-expanded="false"
                                    aria-haspopup="true"
                                    aria-controls="recycle-popover-{{ $document->document_id }}">
                                <i class="fas fa-ellipsis-v" aria-hidden="true"></i>
                            </button>
                            <div id="recycle-popover-{{ $document->document_id }}"
                                 class="recycle-bin-popover"
                                 data-popover-id="{{ $document->document_id }}"
                                 role="menu"
                                 hidden>
                                <form action="{{ route($routePrefix . '.recycle-bin.restore', $document->document_id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="recycle-bin-popover-item recycle-bin-popover-item--restore" role="menuitem">
                                        <i class="fas fa-undo text-xs" aria-hidden="true"></i> Restore
                                    </button>
                                </form>
                                @if($canForceDelete)
                                <form action="{{ route($routePrefix . '.recycle-bin.force-delete', $document->document_id) }}" method="POST" class="recycle-bin-force-delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="recycle-bin-popover-item recycle-bin-popover-item--danger" role="menuitem" onclick="confirmPermanentDelete(this)">
                                        <i class="fas fa-trash-alt text-xs" aria-hidden="true"></i> Permanently Delete
                                    </button>
                                </form>
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $canForceDelete ? 6 : 5 }}">
                        <div class="recycle-bin-empty">
                            <i class="fas fa-recycle recycle-bin-empty__icon" aria-hidden="true"></i>
                            <p class="recycle-bin-empty__title">Recycle Bin is empty</p>
                            <p class="recycle-bin-empty__text">Deleted files from Documents will appear here.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($documents->hasPages())
    <div class="recycle-bin-pagination">
        {{ $documents->links() }}
    </div>
    @endif
</div>

@once
@push('scripts')
<script>
function confirmPermanentDelete(btn) {
    const form = btn.closest('form');
    if (!form) return;
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Delete permanently?',
            text: 'This file will be removed from storage and cannot be recovered. The uploader will be notified.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete forever',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            customClass: { popup: 'swal-flat' }
        }).then((result) => {
            if (result.isConfirmed) form.submit();
        });
        return;
    }
    if (confirm('Delete this file permanently? This cannot be undone. The uploader will be notified.')) {
        form.submit();
    }
}

document.addEventListener('DOMContentLoaded', function () {
    function closePopover(popover, toggleBtn) {
        if (!popover) return;
        popover.classList.remove('is-open');
        popover.setAttribute('hidden', '');
        if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
    }

    function closeAllPopovers() {
        document.querySelectorAll('.recycle-bin-popover').forEach(function (popover) {
            var id = popover.dataset.popoverId;
            var btn = document.querySelector('.recycle-bin-actions-btn[data-doc-id="' + id + '"]');
            closePopover(popover, btn);
        });
    }

    document.querySelectorAll('.recycle-bin-actions-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var id = btn.dataset.docId;
            var popover = document.getElementById('recycle-popover-' + id);
            if (!popover) return;
            var isOpen = popover.classList.contains('is-open');
            closeAllPopovers();
            if (!isOpen) {
                popover.classList.add('is-open');
                popover.removeAttribute('hidden');
                btn.setAttribute('aria-expanded', 'true');
            }
        });
    });

    document.addEventListener('click', function () {
        closeAllPopovers();
    });

    document.querySelectorAll('.recycle-bin-popover').forEach(function (popover) {
        popover.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeAllPopovers();
    });

    var bulkForm = document.getElementById('recycleBinBulkForm');
    var bulkBtn = document.getElementById('recycleBinBulkDeleteBtn');
    var countEl = document.getElementById('recycleBinSelectedCount');
    var selectAllBtn = document.getElementById('recycleBinSelectAll');
    var clearBtn = document.getElementById('recycleBinClearSelection');

    function getRowChecks() {
        return document.querySelectorAll('.recycle-bin-row-check');
    }

    function updateBulkSelection() {
        if (!bulkBtn || !countEl) return;
        var checked = document.querySelectorAll('.recycle-bin-row-check:checked');
        bulkBtn.disabled = checked.length === 0;
        countEl.textContent = checked.length + ' selected';
    }

    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function () {
            getRowChecks().forEach(function (cb) { cb.checked = true; });
            updateBulkSelection();
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            getRowChecks().forEach(function (cb) { cb.checked = false; });
            updateBulkSelection();
        });
    }

    getRowChecks().forEach(function (cb) {
        cb.addEventListener('change', updateBulkSelection);
    });

    if (bulkBtn && bulkForm) {
        bulkBtn.addEventListener('click', function () {
            var checked = document.querySelectorAll('.recycle-bin-row-check:checked');
            if (checked.length === 0) return;

            var submitBulk = function () {
                bulkForm.submit();
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Delete ' + checked.length + ' file(s) permanently?',
                    text: 'These files will be removed from storage and cannot be recovered. Uploaders will be notified.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete forever',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    customClass: { popup: 'swal-flat' }
                }).then(function (result) {
                    if (result.isConfirmed) submitBulk();
                });
                return;
            }

            if (confirm('Permanently delete ' + checked.length + ' selected file(s)?')) {
                submitBulk();
            }
        });
    }

    updateBulkSelection();
});
</script>
@endpush
@endonce
