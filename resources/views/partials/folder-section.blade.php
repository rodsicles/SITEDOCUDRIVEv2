{{-- Folder Management Section --}}
<div class="content-card mb-6">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-folder-tree mr-2"></i> My Folders</h3>
        <button onclick="openCreateFolderModal()" class="btn btn-success">
            <i class="fas fa-folder-plus"></i> New Folder
        </button>
    </div>

    @if($folders->count() > 0)
    <div class="folder-container p-6">
        {{-- Create New Folder Card --}}
        <div class="create-folder-card" onclick="openCreateFolderModal()">
            <div class="text-center">
                <div class="create-folder-icon">
                    <i class="fas fa-folder-plus"></i>
                </div>
                <div class="create-folder-text">Create Folder</div>
            </div>
        </div>

        {{-- Uncategorized Folder --}}
        <a href="{{ request()->fullUrlWithQuery(['folder' => 'uncategorized']) }}" class="folder-card {{ $folderFilter === 'uncategorized' ? 'ring-2 ring-green-500' : '' }}">
            <div class="folder-icon text-gray-400">
                <i class="fas fa-folder-open"></i>
            </div>
            <div class="folder-name">Uncategorized</div>
            <div class="folder-count">
                @php
                    $uncategorizedCount = \App\Models\Document::where('uploaded_by', auth()->id())
                        ->whereNull('folder_id')
                        ->count();
                @endphp
                {{ $uncategorizedCount }} file(s)
            </div>
        </a>

        {{-- User Folders --}}
        @foreach($folders as $folder)
        <div class="folder-card {{ $folder->folder_id == $folderFilter ? 'ring-2 ring-green-500' : '' }}">
            <a href="{{ request()->fullUrlWithQuery(['folder' => $folder->folder_id]) }}" class="block">
                <div class="folder-icon" style="color: {{ $folder->color }}">
                    <i class="fas fa-folder"></i>
                </div>
                <div class="folder-name">{{ $folder->folder_name }}</div>
                <div class="folder-count">{{ $folder->documents_count }} file(s)</div>
            </a>
            <div class="folder-actions">
                <button onclick="event.stopPropagation(); openRenameFolderModal({{ $folder->folder_id }}, '{{ addslashes($folder->folder_name) }}', '{{ $folder->color }}')" class="folder-rename-btn">
                    <i class="fas fa-edit mr-1"></i> Rename
                </button>
                <button onclick="event.stopPropagation(); deleteFolder({{ $folder->folder_id }}, '{{ addslashes($folder->folder_name) }}')" class="folder-delete-btn">
                    <i class="fas fa-trash mr-1"></i> Delete
                </button>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="empty-state">
        <div class="empty-state-icon">
            <i class="fas fa-folder-open"></i>
        </div>
        <div class="empty-state-text mb-4">No folders yet. Create your first folder to organize documents.</div>
        <button onclick="openCreateFolderModal()" class="btn btn-primary">
            <i class="fas fa-folder-plus mr-2"></i> Create Your First Folder
        </button>
    </div>
    @endif
</div>
