@if ($paginator->hasPages())
<nav class="app-pagination" role="navigation" aria-label="Pagination">
    <p class="app-pagination-summary">
        Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
    </p>
    <ul class="app-pagination-list">
        @if ($paginator->onFirstPage())
            <li><span class="app-pagination-btn is-disabled" aria-disabled="true">&laquo; Prev</span></li>
        @else
            <li><a class="app-pagination-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">&laquo; Prev</a></li>
        @endif

        @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
            @if ($page == $paginator->currentPage())
                <li><span class="app-pagination-btn is-active" aria-current="page">{{ $page }}</span></li>
            @else
                <li><a class="app-pagination-btn" href="{{ $url }}">{{ $page }}</a></li>
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <li><a class="app-pagination-btn" href="{{ $paginator->nextPageUrl() }}" rel="next">Next &raquo;</a></li>
        @else
            <li><span class="app-pagination-btn is-disabled" aria-disabled="true">Next &raquo;</span></li>
        @endif
    </ul>
</nav>
@endif
