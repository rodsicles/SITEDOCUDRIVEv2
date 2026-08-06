@extends('layouts.dashboard')

@section('title', $title . ' - Preview')
@section('page-title', $title)
@section('page-subtitle', $folderPath ?? 'File preview')

@section('sidebar')
    @if(auth()->user()->isFaculty())
        @include('partials.faculty-sidebar')
    @elseif(auth()->user()->isProgramCoordinator())
        @include('partials.coordinator-sidebar')
    @elseif(auth()->user()->isSecretary())
        @include('partials.secretary-sidebar')
    @else
        @include('partials.dean-sidebar')
    @endif
@endsection

@section('content')
    <div class="content-card submission-preview-card mb-4">
        <div class="submission-preview-toolbar">
            <div class="doc-action-btns">
                <a href="{{ $streamUrl }}" target="_blank" rel="noopener" class="btn btn-action-view text-xs">
                    <i class="fas fa-external-link-alt"></i> Open in tab
                </a>
                <a href="{{ $downloadUrl }}" class="btn btn-action-download text-xs">
                    <i class="fas fa-download"></i> Download
                </a>
                <a href="{{ $backUrl }}" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
        <div class="submission-preview-frame">
            <iframe src="{{ $streamUrl }}" title="{{ $title }}" class="submission-preview-frame__iframe"></iframe>
        </div>
    </div>

    @isset($versionDocument)
    <div class="content-card mb-4">
        <div class="flex justify-between items-center mb-2">
            <h3 class="card-title text-sm mb-0">
                <i class="fas fa-code-branch mr-2 text-[#028a0f]"></i>Version History
            </h3>
            @if($canManageVersions ?? false)
            <button type="button" onclick="document.getElementById('newVersionForm').classList.toggle('hidden')"
                    class="btn btn-primary text-xs">
                <i class="fas fa-upload"></i> Upload New Version
            </button>
            @endif
        </div>

        @if($canManageVersions ?? false)
        <form id="newVersionForm"
              action="{{ route('documents.versions.store', $versionDocument->document_id) }}"
              method="POST" enctype="multipart/form-data"
              class="hidden border border-gray-200 dark:border-gray-700 p-3 mb-3">
            @csrf
            <div class="flex flex-wrap items-end gap-3">
                <div class="form-group mb-0">
                    <label class="form-label text-xs">Replacement File (PDF/Word, max 10MB)</label>
                    <input type="file" name="file" accept=".pdf,.doc,.docx" class="form-control text-xs" required>
                </div>
                <div class="form-group mb-0 flex-1 min-w-[180px]">
                    <label class="form-label text-xs">Note (optional)</label>
                    <input type="text" name="note" maxlength="255" class="form-control text-xs"
                           placeholder="e.g. Updated grading table">
                </div>
                <button type="submit" class="btn btn-primary text-xs">
                    <i class="fas fa-save"></i> Save Version
                </button>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 mb-0">
                The file currently shown above will be kept as a previous version — walang mabubura.
            </p>
        </form>
        @endif

        @if($versions->count() > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Version</th>
                        <th>Replaced By</th>
                        <th>Date Archived</th>
                        <th>Note</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($versions as $version)
                    <tr>
                        <td><strong>v{{ $version->version_number }}</strong></td>
                        <td>{{ $version->uploader_name }}</td>
                        <td>{{ $version->created_at->format('M d, Y h:i A') }}</td>
                        <td class="text-gray-500 dark:text-gray-400">{{ $version->note ?: '—' }}</td>
                        <td>
                            <div class="doc-action-btns">
                                <a href="{{ route('documents.versions.download', [$versionDocument->document_id, $version->id]) }}"
                                   class="btn btn-action-download text-xs" title="Download this version">
                                    <i class="fas fa-download"></i>
                                </a>
                                @if($canManageVersions ?? false)
                                <form action="{{ route('documents.versions.restore', [$versionDocument->document_id, $version->id]) }}"
                                      method="POST" class="inline"
                                      onsubmit="return confirm('Restore v{{ $version->version_number }} as the current file? Ang kasalukuyang file ay itatago rin bilang bagong version.')">
                                    @csrf
                                    <button type="submit" class="btn btn-action-view text-xs" title="Restore this version">
                                        <i class="fas fa-rotate-left"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-0">
                Isang version pa lang ang file na ito — wala pang naunang kopya.
            </p>
        @endif
    </div>
    @endisset

    @isset($viewers)
    <div class="content-card mb-4">
        <h3 class="card-title text-sm mb-2">
            <i class="fas fa-eye mr-2 text-[#028a0f]"></i>Seen By
        </h3>
        @if($viewers->count() > 0)
            <ul class="text-sm space-y-1">
                @foreach($viewers as $view)
                    <li>
                        <i class="fas fa-check-double text-xs text-gray-400 mr-1"></i>
                        {{ $view->user->employee->full_name ?? $view->user->username ?? 'Unknown user' }}
                        <span class="text-gray-500 dark:text-gray-400">— {{ \Carbon\Carbon::parse($view->viewed_at)->format('M d, Y h:i A') }}</span>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">No one else has viewed this document yet.</p>
        @endif
    </div>
    @endisset
@endsection
