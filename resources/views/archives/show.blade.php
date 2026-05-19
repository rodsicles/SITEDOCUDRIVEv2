@extends('layouts.dashboard')

@section('title', $schoolYear->name . ' - Archive')
@section('page-title', $schoolYear->name)
@section('page-subtitle', 'Archived on ' . $schoolYear->archived_at->format('M d, Y'))

@section('sidebar')
    @include('partials.' . $role . '-sidebar')
@endsection

@section('content')
    <div class="mb-4">
        <a href="{{ route($role . '.archives.list') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
            <i class="fas fa-arrow-left mr-1"></i> Back to Archives
        </a>
    </div>

    {{-- Documents --}}
    <div class="content-card">
        <div class="card-header card-header--archive-docs">
            <h3 class="card-title mb-0"><i class="fas fa-folder mr-2"></i>Documents</h3>
            <form action="{{ route($role . '.archives.show', $schoolYear->id) }}" method="GET" class="archive-doc-search-form" role="search">
                <label class="sr-only" for="archiveDocsSearch">Search archived documents</label>
                <input type="search"
                       id="archiveDocsSearch"
                       name="q"
                       value="{{ request('q', '') }}"
                       class="archive-doc-search-input"
                       placeholder="Search..."
                       autocomplete="off"
                       maxlength="80">
                <button type="submit" class="archive-doc-search-submit" title="Search" aria-label="Search archived documents">
                    <i class="fas fa-search" aria-hidden="true"></i>
                </button>
                @if(request('q'))
                <a href="{{ route($role . '.archives.show', $schoolYear->id) }}"
                   class="archive-doc-search-clear"
                   aria-label="Clear search"
                   title="Clear search">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </a>
                @endif
            </form>
            <span class="badge badge-info">{{ $documents->total() }}</span>
        </div>
        @if($documents->isEmpty())
            <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                @if(request('q'))
                    No documents match your search.
                @else
                    No documents in this archive.
                @endif
            </div>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Uploaded By</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($documents as $doc)
                    <tr>
                        <td><strong>{{ $doc->document_title }}</strong></td>
                        <td>{{ $doc->category ?? '-' }}</td>
                        <td>{{ $doc->uploader->employee->full_name ?? $doc->uploader->username ?? '-' }}</td>
                        <td>{{ $doc->created_at->format('M d, Y') }}</td>
                        <td>
                            <a href="{{ route($role . '.view-document', $doc->document_id) }}" class="btn btn-sm btn-primary border-0" target="_blank">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route($role . '.download-document', $doc->document_id) }}" class="btn btn-sm btn-success border-0">
                                <i class="fas fa-download"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4">{{ $documents->links() }}</div>
        @endif
    </div>

    {{-- Teaching Guides --}}
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-book-open mr-2"></i>Teaching Guides</h3>
            <span class="badge badge-info">{{ $teachingGuides->total() }}</span>
        </div>
        @if($teachingGuides->isEmpty())
            <div class="p-4 text-center text-gray-500 dark:text-gray-400">No teaching guides in this archive.</div>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Subject</th>
                        <th>Uploaded By</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($teachingGuides as $guide)
                    <tr>
                        <td><strong>{{ $guide->title }}</strong></td>
                        <td>{{ $guide->subject ?? '-' }}</td>
                        <td>{{ $guide->uploader->employee->full_name ?? $guide->uploader->username ?? '-' }}</td>
                        <td>{{ $guide->created_at->format('M d, Y') }}</td>
                        <td>
                            <a href="{{ route($role . '.teaching-guides.download', $guide->id) }}" class="btn btn-sm btn-success border-0">
                                <i class="fas fa-download"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4">{{ $teachingGuides->links() }}</div>
        @endif
    </div>

    {{-- Exam Questionnaires --}}
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-file-alt mr-2"></i>Exam Questionnaires</h3>
            <span class="badge badge-info">{{ $examQuestionnaires->total() }}</span>
        </div>
        @if($examQuestionnaires->isEmpty())
            <div class="p-4 text-center text-gray-500 dark:text-gray-400">No exam questionnaires in this archive.</div>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Subject</th>
                        <th>Submitted By</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($examQuestionnaires as $eq)
                    <tr>
                        <td><strong>{{ $eq->title }}</strong></td>
                        <td>{{ $eq->subject ?? '-' }}</td>
                        <td>{{ $eq->submitter->employee->full_name ?? $eq->submitter->username ?? '-' }}</td>
                        <td>
                            <span class="badge badge-{{ $eq->status === 'approved' ? 'success' : ($eq->status === 'rejected' ? 'danger' : 'warning') }}">
                                {{ ucfirst($eq->status) }}
                            </span>
                        </td>
                        <td>{{ $eq->created_at->format('M d, Y') }}</td>
                        <td>
                            <a href="{{ route($role . '.exam-questionnaires.view', $eq->id) }}" class="btn btn-sm btn-primary border-0" target="_blank">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route($role . '.exam-questionnaires.download', $eq->id) }}" class="btn btn-sm btn-success border-0">
                                <i class="fas fa-download"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4">{{ $examQuestionnaires->links() }}</div>
        @endif
    </div>
@endsection
