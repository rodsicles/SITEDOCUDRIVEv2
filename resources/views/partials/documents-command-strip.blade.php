{{-- Thin status + actions bar — additive, same card chrome --}}
@php
    $docsRoute = $docsRoute ?? ($role . '.documents');
    $recycleRoute = $role . '.recycle-bin.index';
    $stripFolders = $displayFolders ?? collect();
    $stripFolderCount = $stripFolders->count();
    $stripFileCount = (int) $stripFolders->sum(fn ($f) => (int) ($f->documents_count ?? 0));
    $stripPending = (int) $stripFolders->sum(fn ($f) => (int) ($f->pending_count ?? 0));
    $stripLast = $stripFolders
        ->map(function ($f) {
            $at = $f->last_document_at ?? null;
            if (is_string($at)) {
                try {
                    return \Illuminate\Support\Carbon::parse($at);
                } catch (\Throwable $e) {
                    return null;
                }
            }

            return $at;
        })
        ->filter()
        ->sortDesc()
        ->first();
    $isLeafStrip = ($isLeafFolder ?? false) && !($isTypeLeafFolder ?? false);
@endphp

@unless($isLeafStrip)
<div class="docs-command-strip">
    <div class="docs-command-strip__stats">
        <span>
            <i class="fas fa-folder mr-1" aria-hidden="true"></i>
            {{ $stripFolderCount }} {{ $stripFolderCount === 1 ? 'folder' : 'folders' }}
        </span>
        <span class="docs-command-strip__dot" aria-hidden="true">·</span>
        <span>
            <i class="fas fa-file mr-1" aria-hidden="true"></i>
            {{ $stripFileCount }} {{ $stripFileCount === 1 ? 'file' : 'files' }} here
        </span>
        @if($stripPending > 0)
            <span class="docs-command-strip__dot" aria-hidden="true">·</span>
            <span class="docs-command-strip__pending">
                <i class="fas fa-clock mr-1" aria-hidden="true"></i>{{ $stripPending }} pending
            </span>
        @endif
        <span class="docs-command-strip__dot" aria-hidden="true">·</span>
        <span class="docs-command-strip__muted">
            Last activity:
            {{ $stripLast ? $stripLast->diffForHumans() : '—' }}
        </span>
    </div>
    <div class="docs-command-strip__actions">
        @if(\Illuminate\Support\Facades\Route::has($recycleRoute))
        <a href="{{ route($recycleRoute) }}" class="docs-command-strip__link">
            <i class="fas fa-trash-restore mr-1" aria-hidden="true"></i> Recycle Bin
        </a>
        @endif
        @if(($isCustomFoldersTab ?? false) && !isset($currentFolder) && ($canUpload ?? false))
        <button type="button"
                class="docs-command-strip__link"
                onclick="typeof toggleCreateCustomFolder === 'function' && toggleCreateCustomFolder()">
            <i class="fas fa-folder-plus mr-1" aria-hidden="true"></i> New folder
        </button>
        @endif
    </div>
</div>
@endunless
