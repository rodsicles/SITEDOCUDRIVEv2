<div id="document-preview-modal" class="fixed inset-0 hidden items-center justify-center bg-black/60 z-50 p-4">
    <div class="bg-white dark:bg-[#1e1e1e] border border-gray-200 dark:border-gray-700 w-full max-w-5xl max-h-[90vh] flex flex-col overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <div>
                <h3 id="document-preview-title" class="text-base font-semibold text-gray-900 dark:text-gray-100">Document Preview</h3>
                <p id="document-preview-subtitle" class="text-xs text-gray-500 dark:text-gray-400">Preview supported for PDF and image files.</p>
            </div>
            <button type="button" onclick="closeDocumentPreview()" class="btn bg-gray-600 text-white text-sm">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
        <div id="document-preview-body" class="flex-1 bg-gray-50 dark:bg-[#111111] min-h-[60vh] overflow-auto"></div>
    </div>
</div>

@push('scripts')
<script>
function openDocumentPreview(button) {
    const modal = document.getElementById('document-preview-modal');
    const title = document.getElementById('document-preview-title');
    const subtitle = document.getElementById('document-preview-subtitle');
    const body = document.getElementById('document-preview-body');
    const previewUrl = button.dataset.previewUrl;
    const previewTitle = button.dataset.previewTitle;
    const previewType = button.dataset.previewType;

    title.textContent = previewTitle || 'Document Preview';
    body.innerHTML = '';

    if (previewType === 'pdf') {
        subtitle.textContent = 'PDF preview';
        body.innerHTML = '<iframe src="' + previewUrl + '#toolbar=0" class="w-full h-[70vh] border-0" title="Document preview"></iframe>';
    } else if (previewType === 'image') {
        subtitle.textContent = 'Image preview';
        body.innerHTML = '<div class="p-4 flex items-center justify-center min-h-[70vh]"><img src="' + previewUrl + '" alt="Document preview" class="max-w-full max-h-[68vh] object-contain"></div>';
    } else {
        subtitle.textContent = 'Inline preview is not available for this file type.';
        body.innerHTML = '<div class="p-6 text-center text-gray-600 dark:text-gray-300">'
            + '<p class="mb-3">This file type opens in a new tab instead of inline preview.</p>'
            + '<a href="' + previewUrl + '" target="_blank" class="btn btn-primary text-sm">Open Document</a>'
            + '</div>';
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('overflow-hidden');
}

function closeDocumentPreview() {
    const modal = document.getElementById('document-preview-modal');
    const body = document.getElementById('document-preview-body');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    body.innerHTML = '';
    document.body.classList.remove('overflow-hidden');
}

document.addEventListener('click', (event) => {
    const modal = document.getElementById('document-preview-modal');
    if (event.target === modal) {
        closeDocumentPreview();
    }
});
</script>
@endpush