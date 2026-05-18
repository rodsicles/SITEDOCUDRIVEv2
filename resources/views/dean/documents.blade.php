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
        $hideDocumentsList = isset($currentFolder)
            && $currentFolder
            && app(\App\Services\AcademicHierarchyService::class)->isSemesterTypeLeafFolder($currentFolder);
    @endphp

    @if(!$hideDocumentsList)
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Available Documents</h3>
            <div class="flex items-center gap-2">
                <span class="badge badge-info">{{ $documents->total() }} Files</span>
                <button type="button" class="btn btn-sm bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300" onclick="toggleDocFilters()">
                    <i class="fas fa-filter"></i> Filters
                </button>
            </div>
        </div>

        <div id="docFiltersPanel" class="documents-filter space-y-4" style="display:none;">
            <form action="{{ route('dean.documents') }}" method="GET" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                <input type="hidden" name="folder" value="{{ $folderFilter }}">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="xl:col-span-2">
                    @include('partials.school-year-filter', ['selected' => request('academic_year', '')])
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-300 block mb-1">Category</label>
                    <select name="category" class="form-control text-sm">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ $categoryFilter === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-300 block mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control text-sm" placeholder="Title or keyword">
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-300 block mb-1">Uploader</label>
                    <select name="uploaded_by" class="form-control text-sm">
                        <option value="">All Uploaders</option>
                        @foreach($uploaders as $uploader)
                            <option value="{{ $uploader->id }}" {{ (string) request('uploaded_by') === (string) $uploader->id ? 'selected' : '' }}>
                                {{ $uploader->employee->full_name ?? $uploader->username }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-300 block mb-1">Uploaded From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control text-sm">
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-300 block mb-1">Uploaded To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control text-sm">
                </div>
                <div class="flex items-end gap-2 md:col-span-2">
                    <button type="submit" class="btn btn-primary text-sm">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <a href="{{ route('dean.documents', array_filter(['folder' => $folderFilter, 'tab' => $tab])) }}" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm">
                        <i class="fas fa-rotate-left"></i> Reset
                    </a>
                </div>
            </form>

            <div class="flex flex-col gap-3 border-t border-gray-200 dark:border-gray-700 pt-4">
                <form action="{{ route('document-filters.store') }}" method="POST" class="flex flex-wrap items-end gap-2">
                    @csrf
                    <input type="hidden" name="category" value="{{ $categoryFilter }}">
                    <input type="hidden" name="folder" value="{{ $folderFilter }}">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <input type="hidden" name="uploaded_by" value="{{ request('uploaded_by') }}">
                    <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                    <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                    <div>
                        <label class="text-xs font-semibold text-gray-600 dark:text-gray-300 block mb-1">Save Current Filters</label>
                        <input type="text" name="name" class="form-control text-sm" placeholder="Filter name" maxlength="50" required>
                    </div>
                    <button type="submit" class="btn btn-primary text-sm">
                        <i class="fas fa-bookmark"></i> Save Filter
                    </button>
                </form>

                @if($savedFilters->isNotEmpty())
                <div class="flex flex-wrap gap-2">
                    @foreach($savedFilters as $savedFilter)
                    <div class="flex items-center gap-2 border border-gray-200 dark:border-gray-700 px-3 py-2 bg-gray-50 dark:bg-[#1e1e1e]">
                        <a href="{{ route('dean.documents', array_merge($savedFilter->toQueryParams(), ['saved_filter' => $savedFilter->document_filter_id])) }}" class="text-sm font-medium text-gray-700 dark:text-gray-200 no-underline">
                            {{ $savedFilter->name }}
                        </a>
                        <form action="{{ route('document-filters.destroy', $savedFilter->document_filter_id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-red-600 dark:text-red-400 bg-transparent border-0 cursor-pointer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
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
                            <a href="{{ route('dean.view-document', $document->document_id) }}" target="_blank" class="btn btn-primary text-xs">
                                <i class="fas fa-eye"></i> View
                            </a>
                            @if($document->document_type === 'word')
                            <div class="doc-download-wrap" style="position:relative;display:inline-block;">
                                <button type="button" class="btn btn-success text-xs" onclick="toggleDownloadMenu({{ $document->document_id }})">
                                    <i class="fas fa-download"></i> Download <i class="fas fa-caret-down"></i>
                                </button>
                                <div id="dl-menu-{{ $document->document_id }}" class="doc-dl-menu" style="display:none;position:absolute;top:100%;left:0;z-index:99;background:#fff;border:1px solid #ccc;min-width:130px;">
                                    <a href="{{ route('dean.download-document', $document->document_id) }}?format=word" class="doc-dl-option"><i class="fas fa-file-word"></i> Word (.docx)</a>
                                    <a href="{{ route('dean.download-document', $document->document_id) }}?format=pdf" class="doc-dl-option"><i class="fas fa-file-pdf"></i> PDF (.pdf)</a>
                                </div>
                            </div>
                            @else
                            <a href="{{ route('dean.download-document', $document->document_id) }}" class="btn btn-success text-xs">
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
function toggleDocFilters() {
    var panel = document.getElementById('docFiltersPanel');
    if (panel.style.display === 'none') {
        panel.style.display = 'block';
    } else {
        panel.style.display = 'none';
    }
}
function confirmDelete(id) {
    Swal.fire({
        title: 'Delete Document?',
        text: 'This document will be removed. The record will be kept for backup.',
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
