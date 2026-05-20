@php
    $routePrefix = $routePrefix ?? (auth()->user()->isFaculty() ? 'faculty' : (auth()->user()->isProgramCoordinator() ? 'coordinator' : 'dean'));
@endphp

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
    window.documentsRenameRouteSample = window.documentsRenameRouteSample || @json(route($routePrefix . '.rename-document', ['id' => 1]));

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
