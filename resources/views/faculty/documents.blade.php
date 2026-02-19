@extends('layouts.dashboard')

@section('title', 'Documents - Faculty')

@section('page-title', 'Documents')
@section('page-subtitle', 'Access shared documents')

@section('sidebar')
    <a href="{{ route('faculty.dashboard') }}" class="menu-item">
        <i class="fas fa-chart-line"></i> Dashboard
    </a>
    <a href="{{ route('faculty.tasks') }}" class="menu-item">
        <i class="fas fa-tasks"></i> My Tasks
    </a>
    <a href="{{ route('faculty.notifications') }}" class="menu-item">
        <i class="fas fa-bell"></i> Notifications
    </a>
    <a href="{{ route('faculty.profile') }}" class="menu-item">
        <i class="fas fa-user"></i> My Profile
    </a>
    <a href="{{ route('faculty.documents') }}" class="menu-item active">
        <i class="fas fa-folder"></i> Documents
    </a>
@endsection

@section('content')
    <!-- Upload Document Section -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Upload Document</h3>
        </div>
        <form action="{{ route('faculty.upload-document') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                <div class="form-group">
                    <label class="form-label">Document Title *</label>
                    <input type="text" name="document_title" class="form-control" placeholder="Enter document title" required>
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
                    <label class="form-label">Tags (comma-separated)</label>
                    <input type="text" name="tags" class="form-control" placeholder="e.g. urgent, confidential, review">
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
                        <div class="w-9 h-9 flex items-center justify-center text-lg rounded-lg bg-gray-100 dark:bg-gray-700">
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
                                    'Policies' => '#1976d2',
                                    'Forms' => '#388e3c',
                                    'Reports' => '#d32f2f',
                                    'Memos' => '#f57c00',
                                    'Research Papers' => '#7b1fa2',
                                    'Other' => '#616161',
                                ];
                                $color = $categoryColors[$document->category] ?? '#616161';
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
                        <a href="{{ route('faculty.view-document', $document->document_id) }}" target="_blank" class="btn btn-primary text-xs mr-1.5">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <a href="{{ route('faculty.download-document', $document->document_id) }}" class="btn btn-success text-xs">
                            <i class="fas fa-download"></i> Download
                        </a>
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

    document.getElementById('uploadForm').addEventListener('submit', function(e) {
        const documentType = document.getElementById('documentType').value;
        const fileInput = document.getElementById('fileInput');
        
        if (!documentType) {
            e.preventDefault();
            alert('Please select a Document Type first!');
            return false;
        }

        if (fileInput.files.length === 0) {
            e.preventDefault();
            alert('Please select at least one file to upload!');
            return false;
        }

        const files = fileInput.files;
        for (let i = 0; i < files.length; i++) {
            const fileName = files[i].name.toLowerCase();
            const fileExtension = fileName.split('.').pop();
            
            if (documentType === 'pdf' && fileExtension !== 'pdf') {
                e.preventDefault();
                alert('Error: You selected "PDF Document" but uploaded a non-PDF file (' + files[i].name + ')');
                return false;
            }
            
            if (documentType === 'image' && !['jpg', 'jpeg', 'png'].includes(fileExtension)) {
                e.preventDefault();
                alert('Error: You selected "Image File" but uploaded an invalid file type (' + files[i].name + ')');
                return false;
            }
        }
    });
</script>
@endpush
