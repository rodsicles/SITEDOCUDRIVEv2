@extends('layouts.dashboard')

@section('title', 'Documents - Faculty')

@section('page-title', 'Documents')
@section('page-subtitle', 'Access shared documents')

@section('sidebar')
    @include('partials.faculty-sidebar')
@endsection

@section('content')
    {{-- Include Folder Management Section --}}
    @include('partials.folder-section')

    <!-- Upload Document Section -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-upload mr-2"></i> Upload Document</h3>
            <button type="button" onclick="toggleUploadForm()" class="btn btn-sm bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300">
                <i id="toggleIcon" class="fas fa-chevron-down"></i>
                <span id="toggleText">Show Form</span>
            </button>
        </div>
        <form action="{{ route('faculty.upload-document') }}" method="POST" enctype="multipart/form-data" id="uploadForm" class="hidden">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                <div class="form-group">
                    <label class="form-label">Folder (Optional)</label>
                    <select name="folder_id" class="form-control">
                        <option value="">No Folder (Uncategorized)</option>
                        @foreach($folders as $folder)
                        <option value="{{ $folder->folder_id }}">{{ $folder->folder_name }}</option>
                        @endforeach
                    </select>
                </div>
            
                <div class="form-group">
                    <label class="form-label">Document Title *</label>
                    <input type="text" name="document_title" class="form-control" placeholder="Enter document title" required maxlength="13">
                </div>

                <div class="form-group">
                    <label class="form-label">Document Type *</label>
                    <select name="document_type" id="documentType" class="form-control" required>
                        <option value="">Select Document Type</option>
                        <option value="pdf">PDF Document</option>
                        <option value="image">Image File</option>
                    </select>
                    <small class="text-xs text-gray-500 dark:text-gray-400 mt-1.5 flex items-center gap-1.5">
                        <i class="fas fa-info-circle"></i> Select file type first before uploading
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-label">Category *</label>
                    <select name="category" class="form-control" required>
                        <option value="">Select Category</option>
                        <option value="Policies">Policies</option>
                        <option value="Forms">Forms</option>
                        <option value="Reports">Reports</option>
                        <option value="Memos">Memos</option>
                        <option value="Research Papers">Research Papers</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Tags (max 15 characters)</label>
                    <input type="text" name="tags" id="tagsInput" class="form-control" placeholder="e.g. urgent" maxlength="15">
                </div>
            </div>

            <div class="form-group mb-5">
                <label class="form-label">Choose Files * (Multiple files supported)</label>
                <input type="file" name="documents[]" id="fileInput" class="form-control" multiple required disabled>
                <small id="fileHelp" class="text-xs text-gray-500 dark:text-gray-400 mt-1.5 flex items-center gap-1.5">
                    <i class="fas fa-lock"></i> Please select a Document Type first to enable file upload
                </small>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-upload"></i> Upload Documents
            </button>
        </form>
    </div>

    <!-- Documents List -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Available Documents</h3>
            <span class="badge badge-info">{{ $documents->total() }} Files</span>
        </div>

        <!-- Category Filter Dropdown -->
        <div class="px-4 pb-4 flex items-center gap-3">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">Filter by Type:</label>
            <select onchange="window.location.href = this.value" class="form-control text-sm max-w-xs">
                <option value="{{ route('faculty.documents') }}">All Documents</option>
                @foreach($categories as $cat)
                    <option value="{{ route('faculty.documents', ['category' => $cat]) }}" {{ $categoryFilter === $cat ? 'selected' : '' }}>
                        {{ $cat }}
                    </option>
                @endforeach
            </select>
        </div>
        <table class="data-table">
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
                <tr>
                    <td>
                        <div class="w-9 h-9 flex items-center justify-center text-lg bg-gray-100 dark:bg-gray-700">
                            @php
                                $extension = strtolower(pathinfo($document->file_path, PATHINFO_EXTENSION));
                            @endphp
                            @if($extension === 'pdf')
                                <i class="fas fa-file-pdf text-red-700"></i>
                            @elseif(in_array($extension, ['png', 'jpg', 'jpeg']))
                                <i class="fas fa-file-image text-blue-700"></i>
                            @else
                                <i class="fas fa-file text-gray-600"></i>
                            @endif
                        </div>
                    </td>
                    <td><strong>{{ $document->document_title }}</strong></td>
                    <td>
                        @if($document->category)
                            @php
                                $categoryColors = [
                                    'Policies' => '#028a0f',
                                    'Forms' => '#028a0f',
                                    'Reports' => '#028a0f',
                                    'Memos' => '#028a0f',
                                    'Research Papers' => '#028a0f',
                                    'Other' => '#028a0f',
                                ];
                                $color = $categoryColors[$document->category] ?? '#028a0f';
                            @endphp
                            <span class="badge" style="background: {{ $color }}; color: white;">
                                {{ $document->category }}
                            </span>
                        @elseif($document->document_type === 'pdf')
                            <span class="badge badge-danger">PDF Document</span>
                        @elseif($document->document_type === 'image')
                            <span class="badge badge-info">Image File</span>
                        @else
                            <span class="badge badge-success">General</span>
                        @endif
                    </td>
                    <td>{{ $document->uploader->employee->full_name ?? $document->uploader->username }}</td>
                    <td>{{ $document->created_at->format('M d, Y') }}</td>
                    <td>
                        <div class="flex gap-1.5 flex-wrap">
                            <a href="{{ route('faculty.view-document', $document->document_id) }}" target="_blank" class="btn btn-primary text-xs">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="{{ route('faculty.download-document', $document->document_id) }}" class="btn btn-success text-xs">
                                <i class="fas fa-download"></i> Download
                            </a>
                            @if($document->uploaded_by === auth()->id())
                            <button onclick="openMoveDocumentModal({{ $document->document_id }})" class="move-folder-btn text-xs">
                                <i class="fas fa-folder-open"></i> Move
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
    </div>
@endsection

@push('scripts')
<script>
    // Toggle Upload Form visibility
    function toggleUploadForm() {
        const form = document.getElementById('uploadForm');
        const icon = document.getElementById('toggleIcon');
        const text = document.getElementById('toggleText');
        
        if (form.classList.contains('hidden')) {
            form.classList.remove('hidden');
            icon.classList.add('rotate-180');
            text.textContent = 'Hide Form';
        } else {
            form.classList.add('hidden');
            icon.classList.remove('rotate-180');
            text.textContent = 'Show Form';
        }
    }

    document.getElementById('documentType').addEventListener('change', function() {
        const fileInput = document.getElementById('fileInput');
        const fileHelp = document.getElementById('fileHelp');
        const selectedType = this.value;

        if (selectedType === '') {
            fileInput.disabled = true;
            fileInput.value = '';
            fileInput.removeAttribute('accept');
            fileHelp.innerHTML = '<i class="fas fa-lock"></i> Please select a Document Type first to enable file upload';
            fileHelp.className = 'text-xs text-gray-500 dark:text-gray-400 mt-1.5 flex items-center gap-1.5';
        } else if (selectedType === 'pdf') {
            fileInput.disabled = false;
            fileInput.setAttribute('accept', '.pdf');
            fileHelp.innerHTML = '<i class="fas fa-file-pdf"></i> Allowed: PDF files only (Max: 10MB each)';
            fileHelp.className = 'text-xs text-red-700 dark:text-red-400 mt-1.5 flex items-center gap-1.5';
        } else if (selectedType === 'image') {
            fileInput.disabled = false;
            fileInput.setAttribute('accept', '.jpg,.jpeg,.png');
            fileHelp.innerHTML = '<i class="fas fa-file-image"></i> Allowed: JPG, JPEG, PNG only (Max: 10MB each)';
            fileHelp.className = 'text-xs text-blue-700 dark:text-blue-400 mt-1.5 flex items-center gap-1.5';
        }
    });

    document.getElementById('uploadForm').addEventListener('submit', async function(e) {
        e.preventDefault(); // Prevent default form submission
        
        const documentType = document.getElementById('documentType').value;
        const fileInput = document.getElementById('fileInput');
        
        if (!documentType) {
            alert('Please select a Document Type first!');
            return false;
        }

        if (fileInput.files.length === 0) {
            alert('Please select at least one file to upload!');
            return false;
        }

        const files = fileInput.files;
        // enforce maximum 3 files
        if (files.length > 3) {
            alert('You can upload a maximum of 3 files per upload.');
            return false;
        }
        for (let i = 0; i < files.length; i++) {
            const fileName = files[i].name.toLowerCase();
            const fileExtension = fileName.split('.').pop();
            
            if (documentType === 'pdf' && fileExtension !== 'pdf') {
                alert('Error: You selected "PDF Document" but uploaded a non-PDF file (' + files[i].name + ')');
                return false;
            }
            
            if (documentType === 'image' && !['jpg', 'jpeg', 'png'].includes(fileExtension)) {
                alert('Error: You selected "Image File" but uploaded an invalid file type (' + files[i].name + ')');
                return false;
            }
        }
        // tags validation: max 15 characters
        const tagsVal = document.getElementById('tagsInput')?.value || '';
        if (tagsVal.trim().length > 15) {
            alert('Please limit tags to 15 characters maximum.');
            return false;
        }

        // Disable submit button and show loading
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
        }

        try {
            const formData = new FormData(this);
            const response = await fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            });

            if (response.status === 429) {
                showToast('Upload limit reached! You can only upload 6 files per hour. Please try again later.', 'error');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
                return;
            }

            const data = await response.json();
            
            if (response.ok) {
                showToast(data.message || 'Documents uploaded successfully!', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast(data.message || 'Upload failed', 'error');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            }
        } catch (error) {
            console.error('Upload error:', error);
            showToast('An error occurred during upload. Please try again.', 'error');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }
    });
</script>
@endpush

{{-- Include Folder Modals --}}
@include('partials.folder-modals')
