@extends('layouts.dashboard')

@section('title', 'Documents - Coordinator')

@section('page-title', 'Document Management')
@section('page-subtitle', 'Upload and manage documents')

@section('sidebar')
    @include('partials.coordinator-sidebar')
@endsection

@section('content')
    {{-- Include Folder Navigation --}}
    @include('partials.folder-tree')

    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Available Documents</h3>
            <span class="badge badge-info">{{ $documents->total() }} Files</span>
        </div>

        <!-- Category Filter + Search -->
        <div class="documents-filter flex items-center gap-4 flex-wrap">
            <div class="flex items-center gap-2">
                <label>Filter by Type:</label>
                <select onchange="window.location.href = this.value" class="form-control text-sm max-w-xs">
                    <option value="{{ route('coordinator.documents') }}">All Documents</option>
                    @foreach($categories as $cat)
                        <option value="{{ route('coordinator.documents', ['category' => $cat]) }}" {{ $categoryFilter === $cat ? 'selected' : '' }}>
                            {{ $cat }}
                        </option>
                    @endforeach
                </select>
            </div>
            <form action="{{ route('coordinator.documents') }}" method="GET" class="flex items-center gap-2 ml-auto">
                @if($categoryFilter)
                <input type="hidden" name="category" value="{{ $categoryFilter }}">
                @endif
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control text-sm pl-9" placeholder="Search documents..." style="min-width: 220px;">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                </div>
                <button type="submit" class="btn btn-primary text-sm">
                    <i class="fas fa-search"></i>
                </button>
                @if(request('search'))
                <a href="{{ route('coordinator.documents', $categoryFilter ? ['category' => $categoryFilter] : []) }}" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm">
                    <i class="fas fa-times"></i>
                </a>
                @endif
            </form>
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
                            <a href="{{ route('coordinator.view-document', $document->document_id) }}" target="_blank" class="btn btn-primary text-xs">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="{{ route('coordinator.download-document', $document->document_id) }}" class="btn btn-success text-xs">
                                <i class="fas fa-download"></i> Download
                            </a>
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
@endsection
