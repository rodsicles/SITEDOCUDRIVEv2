{{-- Extra lines for folder cards: file count, last update, pending — keeps existing card look --}}
@php
    $fileCount = (int) ($folder->documents_count ?? 0);
    $pendingCount = (int) ($folder->pending_count ?? 0);
    $lastAt = $folder->last_document_at ?? null;
    if (is_string($lastAt)) {
        try {
            $lastAt = \Illuminate\Support\Carbon::parse($lastAt);
        } catch (\Throwable $e) {
            $lastAt = null;
        }
    }
@endphp
<div class="folder-count-new">
    {{ $fileCount }} {{ $fileCount === 1 ? 'File' : 'Files' }}
    @if(isset($folder->children) && $folder->children->count() > 0)
        <i class="fas fa-chevron-right ml-1" style="font-size: 0.65rem;"></i>
    @endif
</div>
@if($lastAt)
<div class="folder-meta-new">Updated {{ $lastAt->diffForHumans() }}</div>
@else
<div class="folder-meta-new">No activity yet</div>
@endif
@if($pendingCount > 0)
<div class="folder-pending-new">
    <i class="fas fa-clock" aria-hidden="true"></i>
    {{ $pendingCount }} pending
</div>
@endif
