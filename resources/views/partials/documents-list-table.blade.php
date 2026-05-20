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

@include('partials.rename-document-modal', ['routePrefix' => $routePrefix])
