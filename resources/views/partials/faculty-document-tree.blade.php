{{--
    Folder-based document tree for faculty profile (Dean / Coordinator).
    Collapsed by default; search/filter handled by faculty-profile-documents.
--}}
@php
    if (!function_exists('faculty_doc_tree_count')) {
        function faculty_doc_tree_count($node): int {
            if (!is_array($node) || $node === []) {
                return 0;
            }
            if (array_is_list($node) && isset($node[0]) && is_array($node[0]) && array_key_exists('title', $node[0])) {
                return count($node);
            }
            $total = 0;
            foreach ($node as $child) {
                $total += faculty_doc_tree_count($child);
            }
            return $total;
        }
    }
@endphp

@if(empty($documentTree))
    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
        <i class="fas fa-folder-open text-4xl mb-3 opacity-50"></i>
        <p class="text-sm m-0">No folder structure to browse.</p>
    </div>
@else
    <div class="faculty-doc-tree">
        @foreach($documentTree as $categoryName => $categoryBranch)
            @php $catCount = faculty_doc_tree_count($categoryBranch); @endphp
            <details class="doc-tree-category">
                <summary class="doc-tree-category__summary">
                    <span class="doc-tree-category__label">
                        <i class="fas fa-folder text-[#028a0f]" aria-hidden="true"></i>
                        {{ $categoryName }}
                    </span>
                    <span class="doc-tree-count">{{ $catCount }}</span>
                </summary>
                <div class="doc-tree-category__body">
                    @if(in_array($categoryName, ['Teaching Guides', 'Exam Questionnaires'], true))
                        @include('partials.faculty-document-tree-branch', [
                            'branch' => $categoryBranch,
                            'depth' => 0,
                            'viewRoute' => $viewRoute,
                            'downloadRoute' => $downloadRoute ?? null,
                        ])
                    @else
                        @foreach($categoryBranch as $folderName => $files)
                            @php $folderCount = is_array($files) ? count($files) : 0; @endphp
                            <details class="doc-tree-folder">
                                <summary class="doc-tree-folder__summary">
                                    <span>
                                        <i class="fas fa-folder-open text-amber-600" aria-hidden="true"></i>
                                        {{ $folderName }}
                                    </span>
                                    <span class="doc-tree-count">{{ $folderCount }}</span>
                                </summary>
                                <ul class="doc-tree-files">
                                    @foreach($files as $doc)
                                        @include('partials.faculty-document-tree-item', [
                                            'doc' => $doc,
                                            'viewRoute' => $viewRoute,
                                            'downloadRoute' => $downloadRoute ?? null,
                                        ])
                                    @endforeach
                                </ul>
                            </details>
                        @endforeach
                    @endif
                </div>
            </details>
        @endforeach
    </div>
@endif
