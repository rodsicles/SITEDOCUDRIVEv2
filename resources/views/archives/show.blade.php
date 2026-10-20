@extends('layouts.dashboard')

@section('title', $schoolYear->name . ' - Archive')
@section('page-title', $schoolYear->name)
@section('page-subtitle', 'Archived on ' . $schoolYear->archived_at->format('M d, Y'))

@section('sidebar')
    @include('partials.' . ($sidebarRole ?? $role) . '-sidebar')
@endsection

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route($role . '.archives.list') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
            <i class="fas fa-arrow-left mr-1"></i> Back to Archives
        </a>
        <form action="{{ route($role . '.archives.show', $schoolYear->id) }}" method="GET" class="archive-year-search-form" role="search">
            @if($selectedArchiveFolder)<input type="hidden" name="folder" value="{{ $selectedArchiveFolder->folder_id }}">@endif
            <label class="sr-only" for="archiveYearSearch">Search this archived school year</label>
            <input type="search"
                   id="archiveYearSearch"
                   name="q"
                   value="{{ $archiveSearch ?? request('q', '') }}"
                   class="archive-doc-search-input"
                   placeholder="Search this school year..."
                   autocomplete="off"
                   maxlength="80">
            <button type="submit" class="archive-doc-search-submit" title="Search" aria-label="Search this archived school year">
                <i class="fas fa-search" aria-hidden="true"></i>
            </button>
            @if(!empty($archiveSearch))
            <a href="{{ route($role . '.archives.show', $schoolYear->id) }}"
               class="archive-doc-search-clear"
               aria-label="Clear search"
               title="Clear search">
                <i class="fas fa-times" aria-hidden="true"></i>
            </a>
            @endif
        </form>
    </div>

    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4 -mt-2">
        Search applies only to <strong>{{ $schoolYear->name }}</strong> (documents, teaching guides, and approved exam questionnaires).
    </p>

    <div class="content-card mb-4">
        <div class="card-header">
            <h3 class="card-title mb-0"><i class="fas fa-folder-open mr-2"></i>Folders in this archive</h3>
            @if($canAddToArchive)
                <button type="button" id="openDocumentUpload" class="btn btn-primary btn-sm"><i class="fas fa-upload mr-1" aria-hidden="true"></i> Add files</button>
            @endif
        </div>
        <div class="p-4">
            <div class="flex flex-wrap items-center gap-2 mb-3">
                <a class="btn btn-sm btn-secondary" href="{{ route($role . '.archives.show', $schoolYear) }}">All folders</a>
                @if($selectedArchiveFolder)
                    @foreach($selectedArchiveFolder->getAncestors() as $ancestor)
                        @if((int) $ancestor->school_year_id === (int) $schoolYear->id)
                            <a class="btn btn-sm btn-secondary" href="{{ route($role . '.archives.show', [$schoolYear, 'folder' => $ancestor->folder_id]) }}">{{ app(\App\Services\ArchiveContentService::class)->label($ancestor, $schoolYear) }}</a>
                        @endif
                    @endforeach
                    <strong>{{ app(\App\Services\ArchiveContentService::class)->label($selectedArchiveFolder, $schoolYear) }}</strong>
                @endif
            </div>
            @if($archiveFolders->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">No folders at this level.</p>
            @else
                <div class="flex flex-wrap gap-2 mb-3">
                    @foreach($archiveFolders as $folder)
                        <div class="flex items-center gap-1">
                            <a class="btn btn-sm btn-secondary" href="{{ route($role . '.archives.show', [$schoolYear, 'folder' => $folder->folder_id]) }}"><i class="fas fa-folder mr-1" aria-hidden="true"></i>{{ app(\App\Services\ArchiveContentService::class)->label($folder, $schoolYear) }}</a>
                            @if($folder->created_at && $folder->created_at->gt($schoolYear->archived_at))<span class="badge badge-info">Added later</span>@endif
                            @if(!$folder->is_system && (int) $folder->user_id === (int) auth()->id())
                                <details>
                                    <summary class="btn btn-sm btn-secondary">Rename</summary>
                                    <form method="POST" action="{{ route('archive-contents.folders.rename', [$schoolYear, $folder]) }}" class="flex flex-wrap gap-2 mt-2">
                                        @csrf @method('PATCH')
                                        <label class="sr-only" for="archiveFolderName{{ $folder->folder_id }}">Folder name</label>
                                        <input id="archiveFolderName{{ $folder->folder_id }}" name="folder_name" class="form-control" value="{{ $folder->folder_name }}" maxlength="13" required>
                                        <button class="btn btn-sm btn-primary" type="submit">Save</button>
                                    </form>
                                </details>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
            @if($canAddToArchive && (!$selectedArchiveFolder || !app(\App\Services\ArchiveContentService::class)->isAcademic($selectedArchiveFolder)))
                <form method="POST" action="{{ route('archive-contents.folders.store', $schoolYear) }}" class="flex flex-wrap items-end gap-2">
                    @csrf
                    @if($selectedArchiveFolder)<input type="hidden" name="parent_id" value="{{ $selectedArchiveFolder->folder_id }}">@endif
                    <div><label class="form-label" for="newArchiveFolderName">{{ $selectedArchiveFolder ? 'New subfolder' : 'New archive folder' }}</label><input id="newArchiveFolderName" name="folder_name" class="form-control" maxlength="13" required></div>
                    <button type="submit" class="btn btn-secondary">Create folder</button>
                </form>
            @endif
        </div>
    </div>

    {{-- Documents --}}
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title mb-0"><i class="fas fa-folder mr-2"></i>Documents</h3>
            <span class="badge badge-info">{{ $documents->total() }}</span>
        </div>
        @if($documents->isEmpty())
            <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                @if(!empty($archiveSearch))
                    No documents match your search in this archive.
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
                        <td><strong>{{ $doc->document_title }}</strong> @if($doc->created_at->gt($schoolYear->archived_at))<span class="badge badge-info">Added later</span>@endif</td>
                        <td>{{ $doc->category ?? '-' }}</td>
                        <td>{{ $doc->uploader->employee->full_name ?? $doc->uploader->username ?? '-' }}</td>
                        <td>{{ $doc->created_at->format('M d, Y') }}</td>
                        <td>
                            @include('partials.archive-row-actions', [
                                'viewUrl' => route($role . '.view-document', $doc->document_id),
                                'downloadUrl' => route($role . '.download-document', $doc->document_id),
                                'viewLabel' => 'View ' . $doc->document_title,
                                'downloadLabel' => 'Download ' . $doc->document_title,
                            ])
                            @if(app(\App\Services\DocumentService::class)->userCanRenameDocument($doc, auth()->user()))
                                <details><summary class="btn btn-sm btn-secondary">Rename</summary>
                                    <form method="POST" action="{{ route('archive-contents.documents.rename', [$schoolYear, $doc]) }}" class="flex flex-wrap gap-2 mt-2">@csrf @method('PATCH')<label class="sr-only" for="archiveDocName{{ $doc->document_id }}">File name</label><input id="archiveDocName{{ $doc->document_id }}" name="document_title" class="form-control" value="{{ $doc->document_title }}" maxlength="{{ \App\Support\DocumentNaming::TITLE_MAX_LENGTH }}" required><button class="btn btn-sm btn-primary" type="submit">Save</button></form>
                                </details>
                            @endif
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
            <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                @if(!empty($archiveSearch))
                    No approved teaching guides match your search in this archive.
                @else
                    No approved teaching guides in this archive.
                @endif
            </div>
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
                        <td><strong>{{ $guide->title }}</strong> @if($guide->created_at->gt($schoolYear->archived_at))<span class="badge badge-info">Added later</span>@endif</td>
                        <td>{{ $guide->subject ?? '-' }}</td>
                        <td>{{ $guide->uploader->employee->full_name ?? $guide->uploader->username ?? '-' }}</td>
                        <td>{{ $guide->created_at->format('M d, Y') }}</td>
                        <td>
                            @include('partials.archive-row-actions', [
                                'viewUrl' => route($role . '.teaching-guides.view', $guide->id),
                                'downloadUrl' => route($role . '.teaching-guides.download', $guide->id),
                                'viewLabel' => 'View ' . $guide->title,
                                'downloadLabel' => 'Download ' . $guide->title,
                            ])
                            @if(auth()->user()->isDeanOrSecretary() || auth()->user()->isProgramCoordinator())
                                <details><summary class="btn btn-sm btn-secondary">Rename</summary>
                                    <form method="POST" action="{{ route('archive-contents.guides.rename', [$schoolYear, $guide]) }}" class="flex flex-wrap gap-2 mt-2">@csrf @method('PATCH')<label class="sr-only" for="archiveGuideName{{ $guide->id }}">Teaching guide name</label><input id="archiveGuideName{{ $guide->id }}" name="document_title" class="form-control" value="{{ $guide->title }}" maxlength="{{ \App\Support\DocumentNaming::TITLE_MAX_LENGTH }}" required><button class="btn btn-sm btn-primary" type="submit">Save</button></form>
                                </details>
                            @endif
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
            <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                @if(!empty($archiveSearch))
                    No approved exam questionnaires match your search in this archive.
                @else
                    No approved exam questionnaires in this archive.
                @endif
            </div>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Subject</th>
                        <th>Submitted By</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($examQuestionnaires as $eq)
                    <tr>
                        <td><strong>{{ $eq->title }}</strong> @if($eq->created_at->gt($schoolYear->archived_at))<span class="badge badge-info">Added later</span>@endif</td>
                        <td>{{ $eq->subject ?? '-' }}</td>
                        <td>{{ $eq->submitter->employee->full_name ?? $eq->submitter->username ?? '-' }}</td>
                        <td>{{ $eq->created_at->format('M d, Y') }}</td>
                        <td>
                            @include('partials.archive-row-actions', [
                                'viewUrl' => route($role . '.exam-questionnaires.view', $eq->id),
                                'downloadUrl' => route($role . '.exam-questionnaires.download', $eq->id),
                                'viewLabel' => 'View ' . $eq->title,
                                'downloadLabel' => 'Download ' . $eq->title,
                            ])
                            @if(auth()->user()->isDeanOrSecretary() || auth()->user()->isProgramCoordinator())
                                <details><summary class="btn btn-sm btn-secondary">Rename</summary>
                                    <form method="POST" action="{{ route('archive-contents.questionnaires.rename', [$schoolYear, $eq]) }}" class="flex flex-wrap gap-2 mt-2">@csrf @method('PATCH')<label class="sr-only" for="archiveEqName{{ $eq->id }}">Questionnaire name</label><input id="archiveEqName{{ $eq->id }}" name="document_title" class="form-control" value="{{ $eq->title }}" maxlength="{{ \App\Support\DocumentNaming::TITLE_MAX_LENGTH }}" required><button class="btn btn-sm btn-primary" type="submit">Save</button></form>
                                </details>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4">{{ $examQuestionnaires->links() }}</div>
        @endif
    </div>
    @if($canAddToArchive)
        @include('partials.document-upload-dialog', ['archiveYear' => $schoolYear])
    @endif
@endsection
