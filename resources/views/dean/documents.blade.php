@extends('layouts.dashboard')

@section('title', 'Documents - Dean')

@section('page-title', 'Documents')
@section('page-subtitle', 'View all uploaded documents')

@section('sidebar')
    @include('partials.dean-sidebar')
@endsection

@section('content')
    {{-- Include Folder Navigation --}}
    @include('partials.folder-tree')

    @php
        $hierarchy = app(\App\Services\AcademicHierarchyService::class);
        $hideDocumentsList = isset($currentFolder)
            && $currentFolder
            && (
                $hierarchy->isSemesterTypeLeafFolder($currentFolder)
                || $hierarchy->isTgSemesterFolder($currentFolder)
                || $hierarchy->isTgSubjectFolder($currentFolder)
                || $hierarchy->isEqSemesterFolder($currentFolder)
                || $hierarchy->isEqSubjectFolder($currentFolder)
                || $hierarchy->isEqAssessmentFolder($currentFolder)
            );
    @endphp

    @if(!$hideDocumentsList)
    <div class="content-card">
        @include('partials.documents-filter-panel', [
            'documentsRoute' => 'dean.documents',
            'showSchoolYearFilter' => true,
        ])
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-12"></th>
                    <th>Document Title</th>
                    <th>Type</th>
                    <th>Uploaded By</th>
                    <th>Upload Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($documents as $document)
                <tr>
                    <td>
                        <div class="documents-icon">
                            @php
                                $extension = strtolower(pathinfo($document->file_path, PATHINFO_EXTENSION));
                            @endphp
                            @if($extension === 'pdf')
                                <i class="fas fa-file-pdf"></i>
                            @elseif(in_array($extension, ['doc', 'docx']))
                                <i class="fas fa-file-word"></i>
                            @elseif(in_array($extension, ['png', 'jpg', 'jpeg']))
                                <i class="fas fa-file-image"></i>
                            @else
                                <i class="fas fa-file"></i>
                            @endif
                        </div>
                    </td>
                    <td><strong>{{ $document->document_title }}</strong></td>
                    <td>
                        @if($document->category)
                            <span class="doc-category-badge">{{ $document->category }}</span>
                        @elseif($document->document_type === 'pdf')
                            <span class="doc-category-badge">PDF Document</span>
                        @elseif($document->document_type === 'word')
                            <span class="doc-category-badge">Word Document</span>
                        @elseif($document->document_type === 'image')
                            <span class="doc-category-badge">Image File</span>
                        @else
                            <span class="doc-category-badge">{{ $document->document_type ?? 'General' }}</span>
                        @endif
                    </td>
                    <td>{{ $document->uploader->employee->full_name ?? $document->uploader->username }}</td>
                    <td>{{ $document->created_at->format('M d, Y') }}</td>
                    <td>
                        <div class="doc-action-btns">
                            <a href="{{ route('dean.view-document', $document->document_id) }}" target="_blank" class="btn btn-action-view text-xs">
                                <i class="fas fa-eye"></i> View
                            </a>
                            @if($document->document_type === 'word')
                            <div class="doc-download-wrap" style="position:relative;display:inline-block;">
                                <button type="button" class="btn btn-action-download text-xs" onclick="toggleDownloadMenu({{ $document->document_id }})">
                                    <i class="fas fa-download"></i> Download <i class="fas fa-caret-down"></i>
                                </button>
                                <div id="dl-menu-{{ $document->document_id }}" class="doc-dl-menu" style="display:none;position:absolute;top:100%;left:0;z-index:99;background:#fff;border:1px solid #ccc;min-width:130px;">
                                    <a href="{{ route('dean.download-document', $document->document_id) }}?format=word" class="doc-dl-option"><i class="fas fa-file-word"></i> Word (.docx)</a>
                                    <a href="{{ route('dean.download-document', $document->document_id) }}?format=pdf" class="doc-dl-option"><i class="fas fa-file-pdf"></i> PDF (.pdf)</a>
                                </div>
                            </div>
                            @else
                            <a href="{{ route('dean.download-document', $document->document_id) }}" class="btn btn-action-download text-xs">
                                <i class="fas fa-download"></i> Download
                            </a>
                            @endif
                            <form id="delete-doc-{{ $document->document_id }}" action="{{ route('dean.delete-document', $document->document_id) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                            </form>
                            <button type="button" onclick="confirmDelete({{ $document->document_id }})" class="btn btn-danger text-xs">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-gray-500 dark:text-gray-400 py-8">
                        No documents available
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-5">
            {{ $documents->links() }}
        </div>
    </div>
    @endif


@endsection

@push('scripts')
<script>
function confirmDelete(id) {
    Swal.fire({
        title: 'Move to Recycle Bin?',
        text: 'This file will be removed from Documents and moved to the Recycle Bin. You can restore it later.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        customClass: { popup: 'swal-flat' }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('delete-doc-' + id).submit();
        }
    });
}
</script>
@endpush
