{{-- Amber pending block — own card when used from faculty TG/EQ pages --}}
@if(isset($items) && $items->isNotEmpty())
<div class="pending-dean-approval-card content-card mb-4 border-2 border-amber-300 dark:border-amber-700">
    <div class="card-header bg-amber-50 dark:bg-amber-950/40 border-b border-amber-200 dark:border-amber-800">
        <h3 class="card-title text-amber-900 dark:text-amber-100 mb-0">
            <i class="fas fa-clock mr-2"></i> Pending Dean Approval
        </h3>
        <span class="badge" style="background:#b45309;color:#fff;">{{ $items->count() }}</span>
    </div>
    <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-3 mb-0 p-4">
        @foreach($items as $item)
        @php
            $folder = $item->folder ?? $item->document?->folder ?? null;
            $path = $folder ? \App\Support\SubmissionLocation::folderBreadcrumb($folder) : '';
            $docsUrl = $folder ? \App\Support\SubmissionLocation::documentsUrl(auth()->user(), $folder) : null;
        @endphp
        <li class="pending-dean-approval__item flex flex-wrap items-center justify-between gap-3 py-2 border-b border-amber-100 dark:border-amber-900/50 last:border-0">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    @if(!empty($showRename))
                    <button type="button"
                            class="doc-rename-handle"
                            title="Rename while pending"
                            onclick="openRenameSubmissionModal({{ $item->id }}, @js($item->title))">
                        <i class="fas fa-pen" aria-hidden="true"></i>
                        <span class="doc-rename-label">Rename</span>
                    </button>
                    @endif
                    <strong id="{{ ($titleIdPrefix ?? 'pending-title-') . $item->id }}">{{ $item->title }}</strong>
                </div>
                <span class="text-xs text-gray-500 block mt-1">Uploaded {{ $item->created_at->format('M d, Y') }}</span>
                @if($path)
                <span class="text-xs text-gray-500 dark:text-gray-400 block mt-1">
                    <i class="fas fa-folder-open mr-1"></i>
                    @if($docsUrl)
                    <a href="{{ $docsUrl }}" class="hover:underline">{{ $path }}</a>
                    @else
                    {{ $path }}
                    @endif
                </span>
                @endif
            </div>
            @if(!empty($viewRoute))
            <div class="doc-action-btns shrink-0">
                <a href="{{ route($viewRoute, $item->id) }}" class="btn btn-action-view text-xs py-1 px-2">
                    <i class="fas fa-eye"></i> View
                </a>
                @if(!empty($downloadRoute))
                <a href="{{ route($downloadRoute, $item->id) }}" class="btn btn-action-download text-xs py-1 px-2">
                    <i class="fas fa-download"></i> Download
                </a>
                @endif
            </div>
            @endif
        </li>
        @endforeach
    </ul>
</div>
@endif
