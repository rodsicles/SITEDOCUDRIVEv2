@php
    $routePrefix = $routePrefix ?? 'faculty';
    $canForceDelete = $canForceDelete ?? false;
@endphp

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">Deleted Files</h3>
        <span class="badge badge-info">{{ $documents->total() }} item(s)</span>
    </div>

    <p class="text-sm text-gray-600 dark:text-gray-400 px-4 pb-3 mb-0">
        Files removed from Documents are kept here. Restore puts them back in their original folder when it still exists;
        otherwise they go to <strong>Uncategorized</strong>.
        @if($canForceDelete)
            As Dean, you can permanently delete items — this cannot be undone.
        @endif
    </p>

    <table class="data-table">
        <thead>
            <tr>
                <th>File Name</th>
                <th>Original Folder</th>
                <th>Deleted By</th>
                <th>Deleted At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($documents as $document)
            <tr>
                <td><strong>{{ $document->document_title }}</strong></td>
                <td>{{ $document->recycle_bin_folder_label }}</td>
                <td>{{ $document->deletedBy?->employee?->full_name ?? $document->deletedBy?->username ?? '—' }}</td>
                <td>{{ $document->deleted_at?->format('M d, Y h:i A') ?? '—' }}</td>
                <td>
                    <div class="flex flex-wrap gap-2">
                        <form action="{{ route($routePrefix . '.recycle-bin.restore', $document->document_id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="btn btn-primary text-xs py-1.5 px-3 border-0">
                                <i class="fas fa-undo"></i> Restore
                            </button>
                        </form>
                        @if($canForceDelete)
                        <form action="{{ route($routePrefix . '.recycle-bin.force-delete', $document->document_id) }}" method="POST" class="inline force-delete-form">
                            @csrf
                            @method('DELETE')
                            <button type="button" class="btn btn-danger text-xs py-1.5 px-3 border-0" onclick="confirmPermanentDelete(this)">
                                <i class="fas fa-trash-alt"></i> Delete Permanently
                            </button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center text-gray-500 dark:text-gray-400 py-8">
                    Recycle Bin is empty
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-5">
        {{ $documents->links() }}
    </div>
</div>
