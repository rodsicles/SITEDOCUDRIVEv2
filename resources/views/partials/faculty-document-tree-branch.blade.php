@php
    $depth = $depth ?? 0;
    $icons = ['fa-calendar-alt', 'fa-folder-open', 'fa-book', 'fa-file-alt'];
    $icon = $icons[$depth] ?? 'fa-folder';
@endphp
@foreach($branch as $label => $children)
    @php
        $isDocList = is_array($children)
            && !empty($children)
            && array_is_list($children)
            && is_array($children[0])
            && array_key_exists('title', $children[0]);
    @endphp
    @if($isDocList)
        <ul class="doc-tree-files mb-2 space-y-1" style="margin-left: {{ min($depth * 12, 48) }}px">
            @foreach($children as $doc)
                @include('partials.faculty-document-tree-item', ['doc' => $doc, 'viewRoute' => $viewRoute])
            @endforeach
        </ul>
    @elseif(is_array($children))
        <details class="doc-tree-node mb-1" style="margin-left: {{ min($depth * 8, 32) }}px" {{ $depth < 2 ? 'open' : '' }}>
            <summary class="cursor-pointer py-1.5 text-sm text-gray-700 dark:text-gray-300">
                <i class="fas {{ $icon }} text-[#028a0f] mr-1 w-4"></i>
                {{ $label }}
            </summary>
            <div class="border-l-2 border-gray-200 dark:border-gray-700 ml-4 pl-2 mt-1">
                @include('partials.faculty-document-tree-branch', [
                    'branch' => $children,
                    'depth' => $depth + 1,
                    'viewRoute' => $viewRoute,
                ])
            </div>
        </details>
    @endif
@endforeach
