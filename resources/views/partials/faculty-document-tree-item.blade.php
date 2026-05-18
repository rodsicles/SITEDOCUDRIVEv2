<li class="flex flex-wrap items-center justify-between gap-2 py-2 px-2 rounded hover:bg-gray-50 dark:hover:bg-gray-800/50 text-sm">
    <div class="flex items-center gap-2 min-w-0">
        @if(($doc['type'] ?? '') === 'pdf')
            <i class="fas fa-file-pdf text-red-600"></i>
        @else
            <i class="fas fa-file-word text-blue-600"></i>
        @endif
        <span class="font-medium truncate">{{ $doc['title'] ?? 'Untitled' }}</span>
        @if(!empty($doc['status']))
            <span class="badge {{ $doc['status'] === 'approved' ? 'badge-success' : ($doc['status'] === 'rejected' ? 'badge-danger' : 'badge-warning') }} text-[10px]">
                {{ ucfirst($doc['status']) }}
            </span>
        @endif
    </div>
    <div class="flex items-center gap-2 shrink-0 text-xs text-gray-500">
        <span>{{ isset($doc['created_at']) ? $doc['created_at']->format('M d, Y') : '' }}</span>
        @if(!empty($doc['id']) && is_numeric($doc['id']) && !empty($viewRoute))
            <a href="{{ route($viewRoute, $doc['id']) }}" target="_blank" class="btn btn-primary py-0.5 px-2 text-xs">
                <i class="fas fa-eye"></i> View
            </a>
        @elseif(!empty($doc['is_questionnaire']) || !empty($doc['is_pending_submission']))
            <span class="text-gray-400 text-[10px]">Awaiting approval</span>
        @endif
    </div>
</li>
