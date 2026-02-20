@extends('layouts.dashboard')

@section('title', 'Documents - Coordinator')

@section('page-title', 'Document Management')
@section('page-subtitle', 'Upload and manage documents')

@section('sidebar')
    <a href="{{ route('coordinator.dashboard') }}" class="menu-item">
        <i class="fas fa-chart-line"></i> Dashboard
    </a>
    <a href="{{ route('coordinator.tasks') }}" class="menu-item">
        <i class="fas fa-tasks"></i> Tasks
    </a>
    <a href="{{ route('leave.index') }}" class="menu-item">
        <i class="fas fa-calendar-alt"></i> Leave Requests
    </a>
    <a href="{{ route('calendar.index') }}" class="menu-item">
        <i class="fas fa-calendar"></i> Calendar
    </a>
    <a href="{{ route('coordinator.faculty') }}" class="menu-item">
        <i class="fas fa-users"></i> Faculty Members
    </a>
    <a href="{{ route('coordinator.documents') }}" class="menu-item active">
        <i class="fas fa-folder"></i> Documents
    </a>
@endsection

@section('content')
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Upload New Document</h3>
        </div>
        
        <form action="{{ route('coordinator.upload-document') }}" method="POST" enctype="multipart/form-data" id="uploadFormCoord">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="form-group">
                    <label class="form-label">Document Title *</label>
                    <input type="text" name="document_title" class="form-control" 
                           placeholder="Enter document title" required maxlength="150">
                </div>

                <div class="form-group">
                    <label class="form-label">Document Type *</label>
                    <select name="document_type" id="documentTypeCoord" class="form-control" required>
                        <option value="">Select Document Type</option>
                        <option value="pdf">PDF Document</option>
                        <option value="image">Image File</option>
                    </select>
                    <small class="text-gray-600 dark:text-gray-400 text-xs mt-1.5 block">
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
                    <input type="text" name="tags" class="form-control" placeholder="e.g. urgent, confidential">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Select Files * (Multiple supported)</label>
                <input type="file" name="documents[]" id="fileInputCoord" class="form-control" multiple required disabled>
                <small id="fileHelpCoord" class="text-gray-600 dark:text-gray-400 text-xs mt-1.5 block">
                    <i class="fas fa-lock"></i> Please select a Document Type first to enable file upload
                </small>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-upload"></i> Upload Documents
            </button>
        </form>
    </div>

    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">All Documents</h3>
            <span class="badge badge-info">{{ $documents->total() }} Files</span>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 50px;"></th>
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
                    <td style="text-align: center; font-size: 20px;">
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
                            <span class="badge" style="background: {{ $color }}; color: white">
                                {{ $document->category }}
                            </span>
                        @elseif($document->document_type === 'pdf')
                            <span class="badge" style="background: #d32f2f; color: white">PDF Document</span>
                        @elseif($document->document_type === 'image')
                            <span class="badge" style="background: #1976d2; color: white">Image File</span>
                        @else
                            <span class="badge badge-info">{{ $document->document_type ?? 'General' }}</span>
                        @endif
                    </td>
                    <td>{{ $document->uploader->employee->full_name ?? $document->uploader->username }}</td>
                    <td>{{ $document->created_at->format('M d, Y') }}</td>
                    <td>
                        <a href="{{ route('coordinator.view-document', $document->document_id) }}" target="_blank" class="btn btn-primary text-xs px-4 py-1.5 mr-1">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <a href="{{ route('coordinator.download-document', $document->document_id) }}" class="btn btn-success text-xs px-4 py-1.5">
                            <i class="fas fa-download"></i> Download
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-gray-600 dark:text-gray-400">
                        No documents uploaded yet
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
    document.getElementById('documentTypeCoord').addEventListener('change', function() {
        const fileInput = document.getElementById('fileInputCoord');
        const fileHelp = document.getElementById('fileHelpCoord');
        const selectedType = this.value;

        if (selectedType === '') {
            fileInput.disabled = true;
            fileInput.value = '';
            fileInput.removeAttribute('accept');
            fileHelp.innerHTML = '<i class="fas fa-lock"></i> Please select a Document Type first to enable file upload';
            fileHelp.className = 'text-gray-600 dark:text-gray-400 text-xs mt-1.5 block';
        } else if (selectedType === 'pdf') {
            fileInput.disabled = false;
            fileInput.setAttribute('accept', '.pdf');
            fileHelp.innerHTML = '<i class="fas fa-file-pdf"></i> Allowed: PDF files only (Max: 10MB each)';
            fileHelp.className = 'text-red-700 dark:text-red-500 text-xs mt-1.5 block';
        } else if (selectedType === 'image') {
            fileInput.disabled = false;
            fileInput.setAttribute('accept', '.jpg,.jpeg,.png');
            fileHelp.innerHTML = '<i class="fas fa-file-image"></i> Allowed: JPG, JPEG, PNG only (Max: 10MB each)';
            fileHelp.className = 'text-blue-700 dark:text-blue-500 text-xs mt-1.5 block';
        }
    });

    document.getElementById('uploadFormCoord').addEventListener('submit', function(e) {
        const documentType = document.getElementById('documentTypeCoord').value;
        const fileInput = document.getElementById('fileInputCoord');
        
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
