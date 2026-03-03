{{-- Folder Management Modals --}}

{{-- Create Folder Modal --}}
<div id="createFolderModal" class="modal-overlay" onclick="if(event.target===this)closeCreateFolderModal()">
    <div class="modal-card">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-folder-plus mr-2"></i> Create New Folder</h3>
            <span class="modal-close" onclick="closeCreateFolderModal()">&times;</span>
        </div>
        <form id="createFolderForm" onsubmit="handleCreateFolder(event)">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Folder Name *</label>
                    <input type="text" name="folder_name" id="newFolderName" class="form-input" placeholder="Enter folder name" required maxlength="13" pattern="[a-zA-Z0-9\s\-_]+">
                    <small class="text-xs text-gray-500 dark:text-gray-400 mt-1 block">Only letters, numbers, spaces, hyphens, and underscores allowed</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Color</label>
                    <div class="flex gap-2">
                        <input type="color" name="color" id="folderColor" value="#028a0f" class="form-input w-20 h-10 p-1 cursor-pointer">
                        <input type="text" value="#028a0f" class="form-input flex-1" readonly>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeCreateFolderModal()" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check mr-1"></i> Create Folder
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Rename Folder Modal --}}
<div id="renameFolderModal" class="modal-overlay" onclick="if(event.target===this)closeRenameFolderModal()">
    <div class="modal-card">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-edit mr-2"></i> Rename Folder</h3>
            <span class="modal-close" onclick="closeRenameFolderModal()">&times;</span>
        </div>
        <form id="renameFolderForm" onsubmit="handleRenameFolder(event)">
            @csrf
            @method('PATCH')
            <input type="hidden" name="folder_id" id="renameFolderId">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">New Folder Name *</label>
                    <input type="text" name="folder_name" id="renameFolderName" class="form-input" placeholder="Enter new folder name" required maxlength="13" pattern="[a-zA-Z0-9\s\-_]+">
                </div>
                <div class="form-group">
                    <label class="form-label">Color</label>
                    <div class="flex gap-2">
                        <input type="color" name="color" id="renameFolderColor" class="form-input w-20 h-10 p-1 cursor-pointer">
                        <input type="text" id="renameFolderColorText" class="form-input flex-1" readonly>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeRenameFolderModal()" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-1"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Move Document Modal --}}
<div id="moveDocumentModal" class="modal-overlay" onclick="if(event.target===this)closeMoveDocumentModal()">
    <div class="modal-card">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-arrow-right mr-2"></i> Move to Folder</h3>
            <span class="modal-close" onclick="closeMoveDocumentModal()">&times;</span>
        </div>
        <form id="moveDocumentForm" onsubmit="handleMoveDocument(event)">
            @csrf
            <input type="hidden" name="document_id" id="moveDocumentId">
            <input type="hidden" name="folder_id" id="selectedFolderId">
            <div class="modal-body">
                <div class="mb-4">
                    <button type="button" onclick="showCreateFolderFromMove()" class="btn btn-success w-full">
                        <i class="fas fa-plus mr-1"></i> Create New Folder
                    </button>
                </div>
                <div class="mb-3">
                    <label class="form-label">Select Destination</label>
                </div>
                <div class="folder-list" id="folderListForMove">
                    <div class="folder-list-item" onclick="selectFolderForMove(null, this)">
                        <i class="fas fa-folder-open text-2xl text-gray-400"></i>
                        <div class="flex-1">
                            <div class="font-semibold text-gray-800 dark:text-gray-200">Uncategorized</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">No folder</div>
                        </div>
                    </div>
                    @foreach($folders as $folder)
                    <div class="folder-list-item" onclick="selectFolderForMove({{ $folder->folder_id }}, this)">
                        <i class="fas fa-folder text-2xl" style="color: {{ $folder->color }}"></i>
                        <div class="flex-1">
                            <div class="font-semibold text-gray-800 dark:text-gray-200">{{ $folder->folder_name }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $folder->documents_count }} document(s)</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeMoveDocumentModal()" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check mr-1"></i> Move Document
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
// Modal Functions
function openCreateFolderModal() {
    document.getElementById('createFolderModal').classList.add('active');
    document.getElementById('newFolderName').focus();
}

function closeCreateFolderModal() {
    document.getElementById('createFolderModal').classList.remove('active');
    document.getElementById('createFolderForm').reset();
}

function openRenameFolderModal(folderId, folderName, folderColor) {
    document.getElementById('renameFolderId').value = folderId;
    document.getElementById('renameFolderName').value = folderName;
    document.getElementById('renameFolderColor').value = folderColor;
    document.getElementById('renameFolderColorText').value = folderColor;
    document.getElementById('renameFolderModal').classList.add('active');
    document.getElementById('renameFolderName').focus();
}

function closeRenameFolderModal() {
    document.getElementById('renameFolderModal').classList.remove('active');
    document.getElementById('renameFolderForm').reset();
}

function openMoveDocumentModal(documentId) {
    document.getElementById('moveDocumentId').value = documentId;
    document.getElementById('moveDocumentModal').classList.add('active');
    // Clear previous selection
    document.querySelectorAll('#folderListForMove .folder-list-item').forEach(item => {
        item.classList.remove('selected');
    });
}

function closeMoveDocumentModal() {
    document.getElementById('moveDocumentModal').classList.remove('active');
    document.getElementById('selectedFolderId').value = '';
}

function selectFolderForMove(folderId, element) {
    // Remove selected class from all items
    document.querySelectorAll('#folderListForMove .folder-list-item').forEach(item => {
        item.classList.remove('selected');
    });
    // Add selected class to clicked item
    element.classList.add('selected');
    document.getElementById('selectedFolderId').value = folderId || '';
}

function showCreateFolderFromMove() {
    closeMoveDocumentModal();
    openCreateFolderModal();
}

// Folder CRUD Functions
async function handleCreateFolder(event) {
    event.preventDefault();
    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) submitBtn.disabled = true;
    const formData = new FormData(form);
    
    try {
        const response = await fetch('{{ route(auth()->user()->isDean() ? "dean.folders.store" : (auth()->user()->isProgramCoordinator() ? "coordinator.folders.store" : "faculty.folders.store")) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: formData
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
            showToast('Folder created successfully!', 'success');
            closeCreateFolderModal();
            setTimeout(() => window.location.reload(), 1000);
        } else {
            // Handle validation errors
            if (data.errors) {
                const firstError = Object.values(data.errors)[0][0];
                showToast(firstError, 'error');
            } else {
                showToast(data.message || 'Failed to create folder', 'error');
            }
        }
    } catch (error) {
        showToast('An error occurred. Please try again.', 'error');
        console.error('Folder creation error:', error);
        if (submitBtn) submitBtn.disabled = false;
    }
}

async function handleRenameFolder(event) {
    event.preventDefault();
    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) submitBtn.disabled = true;
    const formData = new FormData(form);
    const folderId = document.getElementById('renameFolderId').value;
    
    try {
        const route = '{{ url("/") }}/' + '{{ auth()->user()->isDean() ? "dean" : (auth()->user()->isProgramCoordinator() ? "coordinator" : "faculty") }}' + '/folders/' + folderId;
        
        const response = await fetch(route, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-HTTP-Method-Override': 'PATCH',
                'Accept': 'application/json',
            },
            body: formData
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
            showToast('Folder renamed successfully!', 'success');
            closeRenameFolderModal();
            setTimeout(() => window.location.reload(), 1000);
        } else {
            if (data.errors) {
                const firstError = Object.values(data.errors)[0][0];
                showToast(firstError, 'error');
            } else {
                showToast(data.message || 'Failed to rename folder', 'error');
            }
        }
    } catch (error) {
        showToast('An error occurred. Please try again.', 'error');
        console.error('Folder rename error:', error);
        if (submitBtn) submitBtn.disabled = false;
    }
}

async function deleteFolder(folderId, folderName) {
    if (!confirm(`Are you sure you want to delete the folder "${folderName}"?\n\nAll documents in this folder will be moved to Uncategorized.`)) {
        return;
    }
    
    try {
        const route = '{{ url("/") }}/' + '{{ auth()->user()->isDean() ? "dean" : (auth()->user()->isProgramCoordinator() ? "coordinator" : "faculty") }}' + '/folders/' + folderId;
        
        const response = await fetch(route, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Folder deleted successfully!', 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showToast(data.message || 'Failed to delete folder', 'error');
        }
    } catch (error) {
        showToast('An error occurred', 'error');
        console.error(error);
    }
}

async function handleMoveDocument(event) {
    event.preventDefault();
    const documentId = document.getElementById('moveDocumentId').value;
    const folderId = document.getElementById('selectedFolderId').value;
    
    try {
        const route = '{{ url("/") }}/' + '{{ auth()->user()->isDean() ? "dean" : (auth()->user()->isProgramCoordinator() ? "coordinator" : "faculty") }}' + '/documents/' + documentId + '/move';
        
        const response = await fetch(route, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ folder_id: folderId || null })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            closeMoveDocumentModal();
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showToast(data.message || 'Failed to move document', 'error');
        }
    } catch (error) {
        showToast('An error occurred', 'error');
        console.error(error);
    }
}

// Toast Notification
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} text-xl"></i>
        <span>${message}</span>
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

// Color picker sync
document.getElementById('folderColor')?.addEventListener('input', (e) => {
    e.target.nextElementSibling.value = e.target.value;
});

document.getElementById('renameFolderColor')?.addEventListener('input', (e) => {
    document.getElementById('renameFolderColorText').value = e.target.value;
});

// Close modals on Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeCreateFolderModal();
        closeRenameFolderModal();
        closeMoveDocumentModal();
    }
});
</script>
@endpush
