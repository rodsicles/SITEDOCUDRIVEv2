@php
    $browseMode = $browseMode ?? 'folder';
    $docsRoute = $docsRoute ?? ($role . '.documents');
    $scopeBase = array_filter([
        'tab' => $tab ?? request('tab'),
        'folder' => isset($currentFolder) && $currentFolder ? $currentFolder->folder_id : null,
    ], fn ($v) => $v !== null && $v !== '');
    $recentCount = isset($recentDocuments) ? $recentDocuments->filter()->count() : 0;
    $favoriteCount = isset($favoriteDocuments) ? $favoriteDocuments->filter()->count() : 0;
@endphp

<div class="doc-scopes">
    <nav class="ui-segmented" aria-label="Document scope">
        <a href="{{ route($docsRoute, $scopeBase) }}"
           class="{{ $browseMode === 'folder' ? 'is-active' : '' }}"
           @if($browseMode === 'folder') aria-current="page" @endif>
            Current Folder
        </a>
        <a href="{{ route($docsRoute, array_merge($scopeBase, ['browse' => 'recent'])) }}"
           class="{{ $browseMode === 'recent' ? 'is-active' : '' }}"
           @if($browseMode === 'recent') aria-current="page" @endif>
            Recent{{ $recentCount ? ' · '.$recentCount : '' }}
        </a>
        <a href="{{ route($docsRoute, array_merge($scopeBase, ['browse' => 'favorites'])) }}"
           class="{{ $browseMode === 'favorites' ? 'is-active' : '' }}"
           @if($browseMode === 'favorites') aria-current="page" @endif>
            Favorites{{ $favoriteCount ? ' · '.$favoriteCount : '' }}
        </a>
    </nav>
    @if($canUpload ?? false)
    <div class="flex items-center gap-2 flex-wrap">
    <button type="button" class="btn btn-secondary" id="openCategoryManager" aria-haspopup="dialog"><i class="fas fa-tags" aria-hidden="true"></i> Manage Categories</button>
    <button type="button" class="btn btn-primary" id="openDocumentUpload" aria-haspopup="dialog">
        <i class="fas fa-upload" aria-hidden="true"></i> Upload Document
    </button>
    </div>
    @else
    <p class="docs-command-strip__muted m-0 text-xs">
        @if($browseMode === 'recent')
            Files you opened recently, across folders.
        @elseif($browseMode === 'favorites')
            Files you starred for quick return.
        @elseif(isset($currentFolder) && $currentFolder)
            Files in this folder.
        @else
            Choose a folder below, or switch scope to Recent or Favorites.
        @endif
    </p>
    @endif
</div>
