{{-- Amber pending block — shared by Documents folder tree, Teaching Guides, Exam Questionnaires --}}
@if(isset($items) && $items->isNotEmpty())
<div class="pending-dean-approval mb-4 p-4 border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/30">
    <h4 class="text-sm font-semibold text-amber-900 dark:text-amber-200 mb-2">
        <i class="fas fa-clock mr-1"></i> Pending Dean approval ({{ $items->count() }})
    </h4>
    <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-2 mb-0">
        @foreach($items as $item)
        <li class="pending-dean-approval__item flex flex-wrap items-center justify-between gap-2">
            <span>
                <strong>{{ $item->title }}</strong>
                <span class="text-xs text-gray-500">— uploaded {{ $item->created_at->format('M d, Y') }}</span>
            </span>
            @if(!empty($viewRoute))
            <div class="doc-action-btns shrink-0">
                <a href="{{ route($viewRoute, $item->id) }}" target="_blank" class="btn btn-action-view text-xs py-1 px-2">
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
