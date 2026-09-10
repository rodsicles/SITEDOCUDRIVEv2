{{-- Recent + Favorites rail — same content-card look, no redesign --}}
@php
    $routePrefix = $routePrefix ?? 'faculty';
    $recentDocuments = ($recentDocuments ?? collect())->filter()->take(5);
    $favoriteDocuments = ($favoriteDocuments ?? collect())->filter()->take(5);
    $hasQuickAccess = $recentDocuments->isNotEmpty() || $favoriteDocuments->isNotEmpty();
@endphp

@if($hasQuickAccess)
<div class="content-card mb-6 docs-quick-access">
    <div class="docs-quick-access__grid">
        <div class="docs-quick-access__col">
            <h4 class="docs-quick-access__title">
                <i class="fas fa-history mr-1" aria-hidden="true"></i> Recent
            </h4>
            @forelse($recentDocuments as $doc)
                @if($doc)
                <a href="{{ route($routePrefix.'.view-document', $doc->document_id) }}" class="docs-quick-access__item">
                    <span class="docs-quick-access__name" title="{{ $doc->document_title }}">{{ $doc->document_title }}</span>
                    <span class="docs-quick-access__meta">{{ optional($doc->created_at)->format('M d') }}</span>
                </a>
                @endif
            @empty
                <p class="docs-quick-access__empty">No recently viewed files yet.</p>
            @endforelse
        </div>
        <div class="docs-quick-access__col">
            <h4 class="docs-quick-access__title">
                <i class="fas fa-star mr-1" aria-hidden="true"></i> Favorites
            </h4>
            @forelse($favoriteDocuments as $doc)
                @if($doc)
                <a href="{{ route($routePrefix.'.view-document', $doc->document_id) }}" class="docs-quick-access__item">
                    <span class="docs-quick-access__name" title="{{ $doc->document_title }}">{{ $doc->document_title }}</span>
                    <span class="docs-quick-access__meta"><i class="fas fa-star text-[#028a0f]" aria-hidden="true"></i></span>
                </a>
                @endif
            @empty
                <p class="docs-quick-access__empty">Star files in the list to pin them here.</p>
            @endforelse
        </div>
    </div>
</div>
@endif
