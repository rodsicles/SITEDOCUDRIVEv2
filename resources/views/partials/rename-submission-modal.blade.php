@php
    $renameRouteSample = $renameRouteSample ?? '';
    $titleIdPrefix = $titleIdPrefix ?? 'submission-title-';
@endphp

<div id="renameSubmissionModal" class="modal-overlay doc-rename-modal" onclick="if(event.target===this)closeRenameSubmissionModal()">
    <div class="modal-card doc-rename-modal-card">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-pen mr-2"></i> Rename File</h3>
            <button type="button" class="modal-close" onclick="closeRenameSubmissionModal()" aria-label="Close">&times;</button>
        </div>
        <form id="renameSubmissionForm" onsubmit="submitRenameSubmission(event)">
            <div class="modal-body">
                <input type="hidden" id="renameSubmissionId" value="">
                <div class="form-group doc-rename-field">
                    <label class="form-label" for="renameSubmissionTitle">File name *</label>
                    <div class="doc-rename-input-box">
                        <input type="text"
                               id="renameSubmissionTitle"
                               name="document_title"
                               class="form-input doc-rename-input"
                               required
                               maxlength="{{ \App\Support\DocumentNaming::TITLE_MAX_LENGTH }}"
                               pattern="[a-zA-Z0-9\s\-_\.]+"
                               autocomplete="off"
                               oninput="updateRenameSubmissionCharCount()">
                    </div>
                    <small class="doc-rename-hint text-xs text-gray-500 dark:text-gray-400 mt-1 block">
                        <span id="renameSubmissionCharCount">0</span>/{{ \App\Support\DocumentNaming::TITLE_MAX_LENGTH }} characters.
                    </small>
                </div>
            </div>
            <div class="modal-footer doc-rename-footer">
                <button type="button" onclick="closeRenameSubmissionModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary doc-rename-save-btn" id="renameSubmissionSubmitBtn">
                    <i class="fas fa-check mr-1"></i> Save
                </button>
            </div>
        </form>
    </div>
</div>

@once
@push('scripts')
<script>
    window.submissionRenameRouteSample = @json($renameRouteSample);
    window.submissionRenameTitlePrefix = @json($titleIdPrefix);

    function updateRenameSubmissionCharCount() {
        const input = document.getElementById('renameSubmissionTitle');
        const counter = document.getElementById('renameSubmissionCharCount');
        if (!input || !counter) return;
        counter.textContent = String(input.value.length);
    }

    function openRenameSubmissionModal(itemId, currentTitle) {
        document.getElementById('renameSubmissionId').value = itemId;
        const input = document.getElementById('renameSubmissionTitle');
        input.value = currentTitle || '';
        updateRenameSubmissionCharCount();
        document.getElementById('renameSubmissionModal').classList.add('active');
        setTimeout(function () { input.focus(); input.select(); }, 50);
    }

    function closeRenameSubmissionModal() {
        const modal = document.getElementById('renameSubmissionModal');
        if (modal) modal.classList.remove('active');
    }

    async function submitRenameSubmission(event) {
        event.preventDefault();
        const btn = document.getElementById('renameSubmissionSubmitBtn');
        const itemId = document.getElementById('renameSubmissionId').value;
        const title = document.getElementById('renameSubmissionTitle').value.trim();
        if (!title || !window.submissionRenameRouteSample) return;

        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        const url = window.submissionRenameRouteSample.replace(/\/1\/rename$/, '/' + itemId + '/rename');

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
                const label = document.getElementById(window.submissionRenameTitlePrefix + itemId);
                if (label) label.textContent = data.document_title || title;
                const subtitle = document.getElementById(window.submissionRenameTitlePrefix + 'sub-' + itemId);
                if (subtitle) subtitle.textContent = data.document_title || title;
                closeRenameSubmissionModal();
                showToast(data.message || 'Renamed successfully.', 'success');
            } else {
                const errors = data.errors ? Object.values(data.errors).flat().join(', ') : (data.message || 'Rename failed.');
                showToast(errors, 'error');
            }
        } catch (e) {
            showToast('Could not rename. Please try again.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = original;
        }
    }
</script>
@endpush
@endonce
