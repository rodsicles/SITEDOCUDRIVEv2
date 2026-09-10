@php
    $searchBits = trim(implode(' ', array_filter([
        $doc['title'] ?? '',
        $doc['subject'] ?? '',
        $doc['type'] ?? '',
        $doc['folder_path'] ?? '',
        $doc['file_name'] ?? '',
        $doc['status'] ?? '',
    ])));
    $canOpen = !empty($doc['id']) && is_numeric($doc['id']) && !empty($viewRoute);
@endphp
<li class="doc-tree-file-item"
    data-search="{{ strtolower($searchBits) }}"
    @if($canOpen) data-doc-id="{{ $doc['id'] }}" @endif>
    <div class="doc-tree-file-item__main">
        @if(($doc['type'] ?? '') === 'pdf')
            <i class="fas fa-file-pdf text-red-600" aria-hidden="true"></i>
        @elseif(in_array($doc['type'] ?? '', ['word', 'doc', 'docx'], true))
            <i class="fas fa-file-word text-blue-600" aria-hidden="true"></i>
        @elseif(($doc['type'] ?? '') === 'image')
            <i class="fas fa-file-image text-green-700" aria-hidden="true"></i>
        @else
            <i class="fas fa-file text-gray-500" aria-hidden="true"></i>
        @endif
        <div class="min-w-0">
            <div class="doc-tree-file-item__title">{{ $doc['title'] ?? 'Untitled' }}</div>
            <div class="doc-tree-file-item__meta">
                @if(!empty($doc['status']))
                    <span class="badge {{ $doc['status'] === 'approved' ? 'badge-success' : ($doc['status'] === 'rejected' ? 'badge-danger' : 'badge-warning') }} text-[10px]">
                        {{ ucfirst($doc['status']) }}
                    </span>
                @endif
                @if(!empty($doc['file_name']) && strcasecmp($doc['file_name'], $doc['title'] ?? '') !== 0)
                    <span class="doc-tree-file-item__filename" title="{{ $doc['file_name'] }}">{{ $doc['file_name'] }}</span>
                @endif
                @if(!empty($doc['folder_path']))
                    <span class="doc-tree-file-item__path" title="{{ $doc['folder_path'] }}">{{ $doc['folder_path'] }}</span>
                @endif
                <span>{{ isset($doc['created_at']) ? $doc['created_at']->format('M d, Y') : '' }}</span>
            </div>
        </div>
    </div>
    <div class="archive-row-actions shrink-0">
        @if($canOpen)
            <a href="{{ route($viewRoute, $doc['id']) }}"
               class="btn btn-sm btn-success border-0 archive-row-actions__btn"
               title="View"
               aria-label="View {{ $doc['title'] ?? 'document' }}">
                <i class="fas fa-eye" aria-hidden="true"></i>
            </a>
            @if(!empty($downloadRoute))
            <a href="{{ route($downloadRoute, $doc['id']) }}"
               class="btn btn-sm btn-success border-0 archive-row-actions__btn"
               title="Download"
               aria-label="Download {{ $doc['title'] ?? 'document' }}">
                <i class="fas fa-download" aria-hidden="true"></i>
            </a>
            @endif
        @elseif(!empty($doc['is_questionnaire']) || !empty($doc['is_pending_submission']))
            <span class="text-gray-400 text-[10px]">Awaiting approval</span>
        @endif
    </div>
</li>
