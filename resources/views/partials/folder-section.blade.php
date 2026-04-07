{{-- Folder Management Section --}}
<div class="content-card mb-6">
    {{-- Header with Title and New Folder Button --}}
    <div class="folder-header-new px-6 py-4 flex items-center justify-between border-b border-gray-200 dark:border-gray-700">
        <h3 class="folder-title-new"><i class="fas fa-folder-tree mr-2"></i> My Folders</h3>
        <button onclick="openCreateFolderModal()" class="btn btn-success">
            <i class="fas fa-plus mr-1"></i> New Folder
        </button>
    </div>

    @if($folders->count() > 0)
    <div class="folder-container-new px-6 py-4 flex gap-3 flex-wrap">
        {{-- Uncategorized Folder --}}
        <div class="folder-card-new {{ request('folder') == 'uncategorized' ? 'folder-card-active' : '' }}">
            <a href="{{ request()->fullUrlWithQuery(['folder' => 'uncategorized']) }}" class="folder-card-link-new">
                <div class="folder-icon-new text-gray-400">
                    <i class="fas fa-folder-open"></i>
                </div>
                <div class="folder-info-new">
                    <div class="folder-name-new">Uncategorized</div>
                    <div class="folder-count-new">
                        @php
                            $uncategorizedCount = \App\Models\Document::where('uploaded_by', auth()->id())
                                ->whereNull('folder_id')
                                ->count();
                        @endphp
                        {{ $uncategorizedCount }} Files
                    </div>
                </div>
            </a>
            <div class="folder-actions-new">
                <button onclick="event.stopPropagation(); openRenameFolderModal('uncategorized', 'Uncategorized', '#6c757d')" class="folder-action-btn" title="Rename">
                    <i class="fas fa-edit"></i>
                </button>
                <button disabled class="folder-action-btn" style="opacity: 0.5; cursor: not-allowed;" title="Cannot delete">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>

        {{-- User Folders --}}
        @foreach($folders as $folder)
        <div class="folder-card-new {{ request('folder') == $folder->folder_id ? 'folder-card-active' : '' }}">
            <a href="{{ request()->fullUrlWithQuery(['folder' => $folder->folder_id]) }}" class="folder-card-link-new">
                <div class="folder-icon-new" style="background-color: {{ $folder->color }}; color: white;">
                    <i class="fas fa-folder"></i>
                </div>
                <div class="folder-info-new">
                    <div class="folder-name-new">{{ $folder->folder_name }}</div>
                    <div class="folder-count-new">{{ $folder->documents_count }} Files</div>
                </div>
            </a>
            <div class="folder-actions-new">
                <button onclick="event.stopPropagation(); openRenameFolderModal({{ $folder->folder_id }}, '{{ addslashes($folder->folder_name) }}', '{{ $folder->color }}')" class="folder-action-btn" title="Rename">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="event.stopPropagation(); deleteFolder({{ $folder->folder_id }}, '{{ addslashes($folder->folder_name) }}')" class="folder-action-btn" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="empty-state p-12 text-center">
        <div class="empty-state-icon mb-3 text-6xl text-gray-300 dark:text-gray-600">
            <i class="fas fa-folder-open"></i>
        </div>
        <div class="empty-state-text mb-6 text-gray-600 dark:text-gray-400">No folders yet. Create your first folder to organize documents.</div>
        <button onclick="openCreateFolderModal()" class="btn btn-success">
            <i class="fas fa-plus mr-1"></i> Create Your First Folder
        </button>
    </div>
    @endif
</div>
