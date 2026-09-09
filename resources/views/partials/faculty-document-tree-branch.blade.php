@php
    $depth = $depth ?? 0;
    $icons = ['fa-calendar-alt', 'fa-book', 'fa-layer-group', 'fa-file-alt'];
    $icon = $icons[min($depth, count($icons) - 1)];
@endphp
@foreach($branch as $label => $children)
    @php
        $isDocList = is_array($children)
            && !empty($children)
            && array_is_list($children)
            && is_array($children[0])
            && array_key_exists('title', $children[0]);

        $childCount = 0;
        if ($isDocList) {
            $childCount = count($children);
        } elseif (is_array($children)) {
            $childCount = function_exists('faculty_doc_tree_count')
                ? faculty_doc_tree_count($children)
                : 0;
        }
    @endphp
    @if($isDocList)
        <ul class="doc-tree-files" style="--tree-depth: {{ $depth }}">
            @foreach($children as $doc)
                @include('partials.faculty-document-tree-item', [
                    'doc' => $doc,
                    'viewRoute' => $viewRoute,
                    'downloadRoute' => $downloadRoute ?? null,
                ])
            @endforeach
        </ul>
    @elseif(is_array($children))
        <details class="doc-tree-node" style="--tree-depth: {{ $depth }}">
            <summary class="doc-tree-node__summary">
                <span>
                    <i class="fas {{ $icon }} text-[#028a0f]" aria-hidden="true"></i>
                    {{ $label }}
                </span>
                <span class="doc-tree-count">{{ $childCount }}</span>
            </summary>
            <div class="doc-tree-node__body">
                @include('partials.faculty-document-tree-branch', [
                    'branch' => $children,
                    'depth' => $depth + 1,
                    'viewRoute' => $viewRoute,
                    'downloadRoute' => $downloadRoute ?? null,
                ])
            </div>
        </details>
    @endif
@endforeach
