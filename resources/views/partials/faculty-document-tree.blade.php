{{--
    Folder-based document tree for faculty profile (Dean / Coordinator).
    @param array $documentTree From FacultyDocumentTreeService
    @param string $viewRoute Route name for document view
    @param string $downloadRoute Route name for document download (optional)
--}}
@php
    $assessmentLabels = config('academic.assessment_periods', []);
    $semesterLabels = config('academic.semesters', []);
@endphp

@if(empty($documentTree))
    <div class="text-center py-10 text-gray-500 dark:text-gray-400">
        <i class="fas fa-folder-open text-5xl mb-4 opacity-50"></i>
        <p>No documents submitted yet</p>
    </div>
@else
    <div class="faculty-doc-tree space-y-4">
        @foreach($documentTree as $categoryName => $categoryBranch)
            <details class="doc-tree-category border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden" open>
                <summary class="cursor-pointer px-4 py-3 bg-gray-50 dark:bg-gray-800 font-semibold flex items-center gap-2">
                    <i class="fas fa-folder text-[#028a0f]"></i>
                    {{ $categoryName }}
                </summary>
                <div class="p-3 pl-4">
                    @if(in_array($categoryName, ['Teaching Guides', 'Exam Questionnaires'], true))
                        @include('partials.faculty-document-tree-branch', [
                            'branch' => $categoryBranch,
                            'depth' => 0,
                            'viewRoute' => $viewRoute,
                        ])
                    @else
                        @foreach($categoryBranch as $folderName => $files)
                            <details class="doc-tree-folder mb-2 ml-2" {{ $loop->first ? 'open' : '' }}>
                                <summary class="cursor-pointer py-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                                    <i class="fas fa-folder-open text-amber-600 mr-1"></i> {{ $folderName }}
                                    <span class="badge badge-info ml-2">{{ is_array($files) ? count($files) : 0 }}</span>
                                </summary>
                                <ul class="ml-6 mt-1 space-y-1">
                                    @foreach($files as $doc)
                                        @include('partials.faculty-document-tree-item', ['doc' => $doc, 'viewRoute' => $viewRoute])
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

