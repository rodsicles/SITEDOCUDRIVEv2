<div id="documentUploadDialog" class="modal-overlay destination-picker-modal" hidden role="dialog" aria-modal="true" aria-labelledby="documentUploadTitle">
    <div class="modal-card destination-picker-card document-upload-card">
        <div class="modal-header destination-picker-header">
            <div><h2 id="documentUploadTitle" class="modal-title">Upload Document</h2><p id="documentUploadStep">Choose a destination</p></div>
            <button type="button" class="modal-close" data-upload-close aria-label="Close upload"><i class="fas fa-times" aria-hidden="true"></i></button>
        </div>
        <div class="modal-body destination-picker-body">
            <p id="documentUploadError" role="alert" hidden></p>
            <section id="uploadDestinationStep" aria-label="Choose destination">
                <div class="upload-picker-shortcuts">
                    <button type="button" class="btn btn-sm btn-secondary" data-upload-scope="folders">Folders</button>
                    <button type="button" class="btn btn-sm btn-secondary" data-upload-scope="recent">Recent destinations</button>
                    <button type="button" class="btn btn-sm btn-secondary" data-upload-scope="favorites">Favorites</button>
                </div>
                <nav id="uploadBreadcrumbs" class="upload-picker-breadcrumbs" aria-label="Destination path"></nav>
                <label for="uploadFolderSearch" class="form-label">Find a folder or course</label>
                <input id="uploadFolderSearch" class="form-control" type="search" placeholder="Search this level…">
                <div id="uploadFolderChoices" class="upload-picker-choices" aria-live="polite"></div>
                <div class="upload-picker-selection"><span id="uploadDestinationHint">Choose a category to begin.</span><button type="button" id="uploadFavorite" class="btn btn-sm btn-secondary" hidden>Save favorite</button></div>
            </section>
            <form id="documentUploadForm" action="{{ route($role . '.upload-document') }}" method="POST" enctype="multipart/form-data" data-custom-submit hidden>
                @csrf
                <input type="hidden" name="folder_id" id="uploadDestinationId">
                <input type="hidden" name="guided_upload" value="1">
                <div class="upload-picker-selection"><strong id="uploadDestinationSummary"></strong><button type="button" id="changeUploadDestination" class="btn btn-sm btn-secondary">Change destination</button></div>
                <div class="form-group"><label for="guidedDocumentTitle" class="form-label">Document title <span class="text-xs">(optional)</span></label><input id="guidedDocumentTitle" name="document_title" class="form-control" maxlength="{{ \App\Support\DocumentNaming::TITLE_MAX_LENGTH }}" placeholder="Leave blank to use each file name"></div>
                <div class="form-group"><label for="guidedDocumentType" class="form-label">File type *</label><select id="guidedDocumentType" name="document_type" class="form-control" required><option value="">Select file type</option><option value="pdf">PDF</option><option value="word">Word Document</option><option value="image">Image</option></select></div>
                <fieldset id="guidedRecipients" hidden disabled>
                    @if(auth()->user()->canUploadSharedDocuments())
                        @include('partials.recipient-picker', ['pickerId' => 'guidedRecipientPicker', 'role' => $role])
                    @endif
                </fieldset>
                <div class="form-group"><label for="guidedDocumentFiles" class="form-label">Choose files *</label><input type="file" id="guidedDocumentFiles" name="documents[]" class="form-control" multiple required><p class="text-xs mt-2">Up to 3 files, 10 MB each. Choose files of the selected type.</p></div>
                <p id="guidedUploadApproval" class="text-xs" hidden>These files will follow the existing review and approval process.</p>
            </form>
        </div>
        <div class="modal-footer destination-picker-footer">
            <button type="button" class="btn btn-secondary" data-upload-close>Cancel</button>
            <button type="button" class="btn btn-primary" id="useUploadDestination" disabled>Use this folder</button>
            <button type="submit" form="documentUploadForm" class="btn btn-primary" id="submitGuidedUpload" hidden><i class="fas fa-upload" aria-hidden="true"></i> Upload Files</button>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('documentUploadDialog');
    const byId = id => document.getElementById(id);
    const form = byId('documentUploadForm');
    const picker = byId('uploadDestinationStep');
    const useButton = byId('useUploadDestination');
    const submit = byId('submitGuidedUpload');
    const storageKey = @json('document-upload-destinations-'.auth()->id());
    const endpoint = @json(route('upload-destinations.index'));
    let state = null, selected = null, busy = false, loading = false, sequence = 0, previousOverflow = '';
    let shortcuts = {recent: [], favorites: []};
    try { const saved = JSON.parse(localStorage.getItem(storageKey)); if (saved) for (const key of ['recent', 'favorites']) shortcuts[key] = Array.isArray(saved[key]) ? saved[key].filter(Number.isInteger).slice(0, 10) : []; } catch (_) {}
    const save = () => { try { localStorage.setItem(storageKey, JSON.stringify(shortcuts)); } catch (_) {} };
    const error = message => { byId('documentUploadError').textContent = message; byId('documentUploadError').hidden = !message; };
    const path = data => data.breadcrumbs.map(folder => folder.name).join(' › ');
    function button(label, action, className = 'btn btn-sm btn-secondary') {
        const item = document.createElement('button'); item.type = 'button'; item.className = className; item.textContent = label; item.addEventListener('click', action); return item;
    }
    async function readFolder(id) {
        const response = await fetch(endpoint + (id ? '?folder=' + encodeURIComponent(id) : ''), {headers: {Accept: 'application/json'}});
        if (!response.ok) throw new Error('This destination is unavailable. Choose another folder or try again.');
        return response.json();
    }
    function showStep(upload) {
        picker.hidden = upload; form.hidden = !upload; useButton.hidden = upload; submit.hidden = !upload;
        byId('documentUploadStep').textContent = upload ? '2 of 2 · Add your files' : '1 of 2 · Choose a destination';
    }
    function renderChoices() {
        const list = byId('uploadFolderChoices'); list.replaceChildren();
        if (!state) return;
        const query = byId('uploadFolderSearch').value.toLowerCase();
        const subjects = state.subjects || [];
        // Semester choices include courses even when their folders have not been created yet.
        const entries = subjects.length ? subjects.map(name => ({name, subject: name})) : state.folders;
        entries.filter(item => item.name.toLowerCase().includes(query)).forEach(item => {
            list.append(button(item.name + ' ›', () => item.subject ? openSubject(item.subject) : browse(item.id), 'upload-picker-folder'));
        });
        if (!list.children.length) list.textContent = state.uploadable ? 'This folder is ready for uploads.' : 'No matching destinations here.';
    }
    function render(data) {
        state = data;
        const crumbs = byId('uploadBreadcrumbs'); crumbs.replaceChildren();
        crumbs.append(button('All categories', () => browse(null)));
        if (data.folder) crumbs.append(button('← Back', () => browse(data.breadcrumbs.at(-2)?.id || null)));
        data.breadcrumbs.forEach(folder => crumbs.append(button(folder.name, () => browse(folder.id))));
        byId('uploadFolderSearch').value = '';
        byId('uploadFolderSearch').disabled = false;
        useButton.disabled = !data.uploadable;
        byId('uploadDestinationHint').textContent = data.uploadable ? path(data) : 'Open a folder to continue.';
        byId('uploadFavorite').hidden = !data.uploadable;
        byId('uploadFavorite').textContent = shortcuts.favorites.includes(data.folder?.id) ? 'Remove favorite' : 'Save favorite';
        renderChoices();
    }
    async function browse(id, autoSelect = false) {
        const ticket = ++sequence; loading = true; error(''); useButton.disabled = true;
        byId('uploadFolderChoices').textContent = 'Loading destinations…';
        try {
            const data = await readFolder(id);
            if (ticket !== sequence) return;
            render(data);
            if (autoSelect && data.uploadable) choose();
        } catch (e) { if (ticket === sequence) { state = null; byId('uploadFolderChoices').replaceChildren(button('Browse all categories', () => browse(null))); error(e.message); } }
        finally { if (ticket === sequence) loading = false; }
    }
    async function openSubject(subject) {
        if (loading || !state?.folder) return;
        loading = true; error('');
        try {
            const response = await fetch(@json(route('upload-destinations.subject')), {method: 'POST', headers: {'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value}, body: JSON.stringify({folder: state.folder.id, subject})});
            if (!response.ok) throw new Error('Unable to open this course. Please try again.');
            const data = await response.json(); await browse(data.id);
        } catch (e) { error(e.message); } finally { loading = false; }
    }
    function choose() {
        if (!state?.uploadable) return;
        selected = state;
        byId('uploadDestinationId').value = selected.folder.id;
        byId('uploadDestinationSummary').textContent = path(selected);
        const image = byId('guidedDocumentType').querySelector('[value="image"]');
        image.hidden = image.disabled = selected.academic;
        if (selected.academic && byId('guidedDocumentType').value === 'image') byId('guidedDocumentType').value = '';
        const recipients = byId('guidedRecipients'); recipients.hidden = recipients.disabled = !selected.academic;
        byId('guidedUploadApproval').hidden = !selected.academic;
        updateAccept(); showStep(true); byId('guidedDocumentTitle').focus();
    }
    function updateAccept() {
        byId('guidedDocumentFiles').accept = ({pdf: '.pdf', word: '.doc,.docx', image: '.jpg,.jpeg,.png,.gif,.webp'})[byId('guidedDocumentType').value] || (selected?.academic ? '.pdf,.doc,.docx' : '.pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.webp');
    }
    byId('guidedDocumentType').addEventListener('change', updateAccept);
    byId('openDocumentUpload').addEventListener('click', () => {
        previousOverflow = document.body.style.overflow; document.body.style.overflow = 'hidden';
        modal.hidden = false; modal.classList.add('active'); showStep(!!selected);
        modal.querySelector('[data-upload-close]').focus();
        if (!selected) browse(@json(isset($currentFolder) ? $currentFolder?->folder_id : null), true);
    });
    function close() { if (busy) return; ++sequence; loading = false; modal.hidden = true; modal.classList.remove('active'); document.body.style.overflow = previousOverflow; byId('openDocumentUpload').focus(); }
    modal.querySelectorAll('[data-upload-close]').forEach(item => item.addEventListener('click', close));
    modal.addEventListener('click', event => { if (event.target === modal) close(); });
    modal.addEventListener('keydown', event => {
        if (event.key === 'Escape') { event.preventDefault(); close(); }
        if (event.key === 'Tab') {
            const elements = [...modal.querySelectorAll('button, input, select, a, textarea')].filter(item => !item.disabled && item.getClientRects().length);
            const first = elements[0], last = elements.at(-1);
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
        }
    });
    byId('changeUploadDestination').addEventListener('click', () => { if (busy) return; showStep(false); browse(selected?.folder.id); });
    byId('uploadFolderSearch').addEventListener('input', renderChoices);
    useButton.addEventListener('click', choose);
    byId('uploadFavorite').addEventListener('click', () => {
        if (!state?.uploadable || loading) return;
        const id = state.folder.id;
        shortcuts.favorites = shortcuts.favorites.includes(id) ? shortcuts.favorites.filter(value => value !== id) : [id, ...shortcuts.favorites].slice(0, 10);
        save(); render(state);
    });
    modal.querySelectorAll('[data-upload-scope]').forEach(item => item.addEventListener('click', async () => {
        const scope = item.dataset.uploadScope;
        if (scope === 'folders') { browse(null); return; }
        const ticket = ++sequence; loading = true; state = null; useButton.disabled = true; error('');
        byId('uploadFolderSearch').value = ''; byId('uploadFolderSearch').disabled = true;
        byId('uploadFavorite').hidden = true; byId('uploadBreadcrumbs').textContent = item.textContent;
        byId('uploadDestinationHint').textContent = 'Shortcuts are saved in this browser.';
        const list = byId('uploadFolderChoices'); list.textContent = 'Loading…';
        const results = await Promise.allSettled(shortcuts[scope].map(readFolder));
        if (ticket !== sequence) return;
        list.replaceChildren();
        results.forEach(result => { if (result.status === 'fulfilled' && result.value.uploadable) list.append(button(path(result.value), () => browse(result.value.folder.id), 'upload-picker-folder')); });
        if (!list.children.length) list.textContent = scope === 'recent' ? 'Your recent upload destinations will appear here.' : 'Open a destination and select Save favorite.';
        loading = false;
    }));
    form.addEventListener('submit', async event => {
        event.preventDefault(); if (busy || !selected) return;
        error('');
        const files = [...byId('guidedDocumentFiles').files];
        const extensions = ({pdf: ['pdf'], word: ['doc','docx'], image: ['jpg','jpeg','png','gif','webp']})[byId('guidedDocumentType').value] || [];
        if (!files.length || files.length > 3 || files.some(file => file.size > 10 * 1024 * 1024 || !extensions.includes(file.name.split('.').pop().toLowerCase()))) { error('Choose up to 3 files of the selected type, no larger than 10 MB each.'); return; }
        busy = true; submit.disabled = true; submit.textContent = 'Uploading…';
        try {
            const response = await fetch(form.action, {method: 'POST', body: new FormData(form), headers: {Accept: 'application/json'}});
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.errors ? Object.values(data.errors).flat().join(' ') : data.message || 'Upload failed. Please try again.');
            shortcuts.recent = [selected.folder.id, ...shortcuts.recent.filter(id => id !== selected.folder.id)].slice(0, 10); save();
            byId('documentUploadStep').textContent = data.message || 'Upload complete.';
            window.location.assign(selected.url);
        } catch (e) { error(e.message === 'Failed to fetch' ? 'Connection interrupted. Check Documents before retrying to avoid uploading the same files twice.' : e.message); }
        finally { busy = false; submit.disabled = false; submit.textContent = 'Upload Files'; }
    });
});
</script>
@endpush
