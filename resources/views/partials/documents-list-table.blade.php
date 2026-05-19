@php
    $routePrefix = $routePrefix ?? 'faculty';
    $user = auth()->user();
    $documentService = app(\App\Services\DocumentService::class);
@endphp

<table class="data-table" id="documentsListTable">
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
        @php
            $extension = strtolower(pathinfo($document->file_path, PATHINFO_EXTENSION));
            $canRename = $documentService->userCanRenameDocument($document, $user);
            $canDelete = $user->isDean() || $user->isSecretary() || (int) $document->uploaded_by === (int) $user->id;
        @endphp
        <tr>
            <td>
                <div class="w-9 h-9 flex items-center justify-center text-lg bg-gray-100 dark:bg-gray-700 documents-icon">
                    @if($extension === 'pdf')
                        <i class="fas fa-file-pdf text-red-700"></i>
                    @elseif(in_array($extension, ['doc', 'docx']))
                        <i class="fas fa-file-word text-blue-700"></i>
                    @elseif(in_array($extension, ['png', 'jpg', 'jpeg']))
                        <i class="fas fa-file-image text-blue-700"></i>
                    @else
                        <i class="fas fa-file text-gray-600"></i>
                    @endif
                </div>
            </td>
            <td>
                <div class="doc-title-cell">
                    @if($canRename)
                    <button type="button"
                            class="doc-rename-handle"
                            title="Rename document"
                            aria-label="Rename {{ $document->document_title }}"
                            onclick="openRenameDocumentModal({{ $document->document_id }}, @js($document->document_title))">
                        <i class="fas fa-pen" aria-hidden="true"></i>
                        <span class="doc-rename-label">Rename</span>
                    </button>
                    @endif
                    <strong class="doc-title-text" id="doc-title-text-{{ $document->document_id }}">{{ $document->document_title }}</strong>
                </div>
            </td>
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
                    <a href="{{ route($routePrefix . '.view-document', $document->document_id) }}" target="_blank" class="btn btn-action-view text-xs">
                        <i class="fas fa-eye"></i> View
                    </a>
                    @if($document->document_type === 'word')
                    <div class="doc-download-wrap" style="position:relative;display:inline-block;">
                        <button type="button" class="btn btn-action-download text-xs" onclick="toggleDownloadMenu({{ $document->document_id }})">
                            <i class="fas fa-download"></i> Download <i class="fas fa-caret-down"></i>
                        </button>
                        <div id="dl-menu-{{ $document->document_id }}" class="doc-dl-menu" style="display:none;position:absolute;top:100%;left:0;z-index:99;background:#fff;border:1px solid #ccc;min-width:130px;">
                            <a href="{{ route($routePrefix . '.download-document', $document->document_id) }}?format=word" class="doc-dl-option"><i class="fas fa-file-word"></i> Word (.docx)</a>
                            <a href="{{ route($routePrefix . '.download-document', $document->document_id) }}?format=pdf" class="doc-dl-option"><i class="fas fa-file-pdf"></i> PDF (.pdf)</a>
                        </div>
                    </div>
                    @else
                    <a href="{{ route($routePrefix . '.download-document', $document->document_id) }}" class="btn btn-action-download text-xs">
                        <i class="fas fa-download"></i> Download
                    </a>
                    @endif
                    @if($canDelete)
                    <form id="delete-doc-{{ $document->document_id }}" action="{{ route($routePrefix . '.delete-document', $document->document_id) }}" method="POST" class="d-inline">
                        @csrf @method('DELETE')
                    </form>
                    <button type="button" onclick="confirmDelete({{ $document->document_id }})" class="btn btn-danger text-xs">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                    @endif
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

<div id="renameDocumentModal" class="modal-overlay doc-rename-modal" onclick="if(event.target===this)closeRenameDocumentModal()">
    <div class="modal-card doc-rename-modal-card">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-pen mr-2"></i> Rename Document</h3>
            <button type="button" class="modal-close" onclick="closeRenameDocumentModal()" aria-label="Close">&times;</button>
        </div>
        <form id="renameDocumentForm" onsubmit="submitRenameDocument(event)">
            <div class="modal-body">
                <input type="hidden" id="renameDocumentId" name="document_id" value="">
                <div class="form-group doc-rename-field">
                    <label class="form-label" for="renameDocumentTitle">Document name *</label>
                    <div class="doc-rename-input-box">
                        <input type="text"
                               id="renameDocumentTitle"
                               name="document_title"
                               class="form-input doc-rename-input"
                               required
                               maxlength="{{ \App\Support\DocumentNaming::TITLE_MAX_LENGTH }}"
                               pattern="[a-zA-Z0-9\s\-_\.]+"
                               autocomplete="off"
                               oninput="updateRenameCharCount()">
                    </div>
                    <small class="doc-rename-hint text-xs text-gray-500 dark:text-gray-400 mt-1 block">
                        <span id="renameDocumentCharCount">0</span>/{{ \App\Support\DocumentNaming::TITLE_MAX_LENGTH }} characters. Updates for everyone immediately.
                    </small>
                </div>
            </div>
            <div class="modal-footer doc-rename-footer">
                <button type="button" onclick="closeRenameDocumentModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary doc-rename-save-btn" id="renameDocumentSubmitBtn">
                    <i class="fas fa-check mr-1"></i> Save
                </button>
            </div>
        </form>
    </div>
</div>

@once
@push('scripts')
<script>
    window.documentsRenameRouteSample = @json(route($routePrefix . '.rename-document', ['id' => 1]));

    function updateRenameCharCount() {
        const input = document.getElementById('renameDocumentTitle');
        const counter = document.getElementById('renameDocumentCharCount');
        if (!input || !counter) return;
        counter.textContent = String(input.value.length);
    }

    function openRenameDocumentModal(documentId, currentTitle) {
        document.getElementById('renameDocumentId').value = documentId;
        const input = document.getElementById('renameDocumentTitle');
        input.value = currentTitle || '';
        updateRenameCharCount();
        document.getElementById('renameDocumentModal').classList.add('active');
        setTimeout(function () { input.focus(); input.select(); }, 50);
    }

    function closeRenameDocumentModal() {
        const modal = document.getElementById('renameDocumentModal');
        if (modal) modal.classList.remove('active');
    }

    async function submitRenameDocument(event) {
        event.preventDefault();
        const btn = document.getElementById('renameDocumentSubmitBtn');
        const documentId = document.getElementById('renameDocumentId').value;
        const title = document.getElementById('renameDocumentTitle').value.trim();
        if (!title) return;

        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        const url = window.documentsRenameRouteSample.replace(/\/1\/rename$/, '/' + documentId + '/rename');

        try {
            const response = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ document_title: title }),
            });

            const data = await response.json();

            if (response.ok && data.success) {
                const label = document.getElementById('doc-title-text-' + documentId);
                if (label) label.textContent = data.document_title || title;
                closeRenameDocumentModal();
                showToast(data.message || 'Document renamed.', 'success');
            } else {
                const errors = data.errors ? Object.values(data.errors).flat().join(', ') : (data.message || 'Rename failed.');
                showToast(errors, 'error');
            }
        } catch (e) {
            showToast('Could not rename document. Please try again.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = original;
        }
    }
</script>
@endpush
@endonce
