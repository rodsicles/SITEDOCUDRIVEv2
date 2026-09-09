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
    @php
        $hasVersionsPanel = ($canVersion ?? false) || (!empty($versions) && $versions->count() > 0);
        $hasSeenByPanel = isset($viewers);
        $showSidePanel = $hasVersionsPanel || $hasSeenByPanel;
        $isArchivedPreview = (bool) ($isArchivedVersion ?? false);
    @endphp

    <div class="submission-preview-layout {{ $showSidePanel ? 'submission-preview-layout--with-side' : '' }}">
        <div class="submission-preview-main">
            <div class="content-card submission-preview-card mb-0">
                <div class="submission-preview-toolbar">
                    <div class="doc-action-btns">
                        @if($isArchivedPreview)
                        <span class="badge bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200 text-xs mr-auto">
                            Read-only archive · v{{ $currentVersion ?? '' }}
                        </span>
                        @endif
                        <a href="{{ $streamUrl }}" target="_blank" rel="noopener" class="btn btn-action-view text-xs">
                            <i class="fas fa-external-link-alt"></i> Open in tab
                        </a>
                        <a href="{{ $downloadUrl }}" class="btn btn-action-download text-xs">
                            <i class="fas fa-download"></i> Download
                        </a>
                        @if(($canCopy ?? false) && ! $isArchivedPreview)
                        <button type="button" class="btn bg-blue-600 text-white text-xs"
                                onclick="openPreviewCopyModal()">
                            <i class="fas fa-copy"></i> Copy to…
                        </button>
                        @endif
                        @if(($canVersion ?? false) && ! $isArchivedPreview)
                        <button type="button" class="btn bg-indigo-600 text-white text-xs"
                                onclick="document.getElementById('versionUploadBackdrop').classList.add('open')">
                            <i class="fas fa-code-branch"></i> Upload New Version
                        </button>
                        @endif
                        <a href="{{ $backUrl }}" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                <div class="submission-preview-frame">
                    @if($isImage ?? false)
                        <div class="submission-preview-image-wrap">
                            <img src="{{ $streamUrl }}" alt="{{ $title }}" class="submission-preview-image">
                        </div>
                    @else
                        <iframe src="{{ $streamUrl }}" title="{{ $title }}" class="submission-preview-frame__iframe"></iframe>
                    @endif
                </div>
            </div>
        </div>

        @if($showSidePanel)
        <aside class="submission-preview-side" aria-label="Document details">
            @if($hasVersionsPanel)
            <div class="content-card submission-preview-side-card">
                <div class="card-header">
                    <h3 class="card-title text-sm">
                        <i class="fas fa-history mr-2 text-[#028a0f]"></i>Version History
                    </h3>
                    <span class="badge bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs">
                        Current: v{{ $currentVersion ?? 1 }}
                    </span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                    Previous versions are read-only. View or download below.
                </p>
                @if(!empty($versions) && $versions->count() > 0)
                <div class="version-history-list">
                    @foreach($versions as $ver)
                    <div class="version-history-row version-history-row--side">
                        <div class="version-badge">v{{ $ver->version_number }}</div>
                        <div class="version-info">
                            <span class="version-uploader">
                                {{ $ver->uploader?->employee?->full_name ?? $ver->uploader?->username ?? 'Unknown' }}
                            </span>
                            <span class="version-date">
                                {{ $ver->created_at?->format('M d, Y g:i A') ?? '—' }}
                            </span>
                            @if($ver->note)
                            <span class="version-notes">{{ $ver->note }}</span>
                            @endif
                            <span class="version-size">{{ $ver->fileSizeFormatted() }}</span>
                        </div>
                        <div class="version-history-actions">
                            <a href="{{ route('document-versions.view', $ver->id) }}"
                               class="btn btn-action-view text-xs"
                               title="View v{{ $ver->version_number }}">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="{{ route('document-versions.download', $ver->id) }}"
                               class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs"
                               title="Download v{{ $ver->version_number }}">
                                <i class="fas fa-download"></i>
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    No previous versions yet. Upload a new version to start history.
                </p>
                @endif
            </div>
            @endif

            @if($hasSeenByPanel)
            <div class="content-card submission-preview-side-card">
                <h3 class="card-title text-sm mb-2">
                    <i class="fas fa-eye mr-2 text-[#028a0f]"></i>Seen By
                </h3>
                @if($viewers->count() > 0)
                    <ul class="text-sm space-y-1 submission-preview-seen-list">
                        @foreach($viewers as $view)
                            <li>
                                <i class="fas fa-check-double text-xs text-gray-400 mr-1"></i>
                                {{ $view->user->employee->full_name ?? $view->user->username ?? 'Unknown user' }}
                                <span class="text-gray-500 dark:text-gray-400 block text-xs pl-4">
                                    {{ \Carbon\Carbon::parse($view->viewed_at)->format('M d, Y h:i A') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">No one else has viewed this document yet.</p>
                @endif
            </div>
            @endif
        </aside>
        @endif
    </div>

    {{-- ── Version Upload Modal ────────────────────────────────────────── --}}
    @if(($canVersion ?? false) && ! $isArchivedPreview)
    <div id="versionUploadBackdrop" class="version-upload-modal-backdrop" aria-modal="true" role="dialog">
        <div class="version-upload-modal">
            <h4 class="text-sm font-bold mb-1">
                <i class="fas fa-code-branch mr-2 text-indigo-600"></i>Upload New Version
            </h4>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                The current file (v{{ $currentVersion ?? 1 }}) will be saved to Version History.
                The new file becomes <strong>v{{ ($currentVersion ?? 1) + 1 }}</strong>
                and the document will be renamed to include that version
                (e.g. Title (v{{ ($currentVersion ?? 1) + 1 }})).
            </p>
            <form method="POST" action="{{ $versionUploadUrl }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label class="form-label">New File * <span class="text-xs font-normal text-gray-500">(max 20 MB)</span></label>
                    <input type="file" name="file" class="form-control" required
                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.webp">
                </div>
                <div class="form-group">
                    <label class="form-label">What changed? <span class="text-xs font-normal text-gray-500">(optional)</span></label>
                    <input type="text" name="notes" class="form-control" maxlength="255"
                           placeholder="e.g. Corrected exam dates on page 2">
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="btn btn-primary text-xs">
                        <i class="fas fa-upload mr-1"></i> Upload Version
                    </button>
                    <button type="button" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs"
                            onclick="document.getElementById('versionUploadBackdrop').classList.remove('open')">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ── Copy Document Modal (preview page) ─────────────────────────── --}}
    @if(($canCopy ?? false) && ! $isArchivedPreview)
    <div id="previewCopyBackdrop" class="copy-doc-modal-backdrop" aria-modal="true" role="dialog">
        <div class="copy-doc-modal">
            <h4><i class="fas fa-copy mr-2 text-[#028a0f]"></i>Copy Document To…</h4>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Choose a destination folder for the duplicate.</p>
            <select id="previewCopyFolderSelect" class="form-control mb-3">
                <option value="">— Root Documents (no folder) —</option>
            </select>
            <div class="flex gap-3">
                <button type="button" id="previewCopyConfirmBtn" class="btn btn-primary text-xs">
                    <i class="fas fa-copy mr-1"></i> Copy
                </button>
                <button type="button" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs"
                        onclick="document.getElementById('previewCopyBackdrop').classList.remove('open')">
                    Cancel
                </button>
            </div>
            <p id="previewCopyStatus" class="text-xs mt-2 hidden"></p>
        </div>
    </div>

    <script>
    (function() {
        var copyUrl      = @json($copyUrl ?? '');
        var foldersUrl   = @json($foldersListUrl ?? '');
        var foldersLoaded = false;

        window.openPreviewCopyModal = function() {
            var backdrop = document.getElementById('previewCopyBackdrop');
            var select   = document.getElementById('previewCopyFolderSelect');
            var status   = document.getElementById('previewCopyStatus');
            if (!backdrop) return;
            status.classList.add('hidden');
            backdrop.classList.add('open');

            if (foldersLoaded || !foldersUrl) return;
            fetch(foldersUrl)
                .then(r => r.json())
                .then(function(data) {
                    (data.folders || []).forEach(function(f) {
                        var opt = document.createElement('option');
                        opt.value = f.folder_id;
                        opt.textContent = f.folder_name;
                        select.appendChild(opt);
                    });
                    foldersLoaded = true;
                });
        };

        document.addEventListener('DOMContentLoaded', function() {
            var btn = document.getElementById('previewCopyConfirmBtn');
            var backdrop = document.getElementById('previewCopyBackdrop');
            var status  = document.getElementById('previewCopyStatus');

            if (backdrop) {
                backdrop.addEventListener('click', function(e) {
                    if (e.target === backdrop) backdrop.classList.remove('open');
                });
            }

            if (btn) {
                btn.addEventListener('click', function() {
                    var select   = document.getElementById('previewCopyFolderSelect');
                    var folderId = select ? select.value : '';
                    var body     = new FormData();
                    body.append('_token', document.querySelector('meta[name=csrf-token]')?.content || '');
                    if (folderId) body.append('folder_id', folderId);

                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Copying…';
                    status.classList.add('hidden');

                    fetch(copyUrl, { method: 'POST', body: body })
                        .then(r => r.json())
                        .then(function(data) {
                            status.textContent = data.message || 'Done.';
                            status.style.color  = data.success ? '#028a0f' : '#dc2626';
                            status.classList.remove('hidden');
                            if (data.success) setTimeout(function() { backdrop.classList.remove('open'); }, 1400);
                        })
                        .catch(function() {
                            status.textContent = 'An error occurred.';
                            status.style.color = '#dc2626';
                            status.classList.remove('hidden');
                        })
                        .finally(function() {
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fas fa-copy mr-1"></i> Copy';
                        });
                });
            }

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    document.getElementById('previewCopyBackdrop')?.classList.remove('open');
                    document.getElementById('versionUploadBackdrop')?.classList.remove('open');
                }
            });
        });
    })();
    </script>
    @endif
@endsection
