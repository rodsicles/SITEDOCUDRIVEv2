@php
    $docTitleMax = \App\Support\DocumentNaming::TITLE_MAX_LENGTH;
@endphp
<div id="folderUploadModal"
     class="modal-overlay site-upload-modal"
     role="dialog"
     aria-modal="true"
     aria-hidden="true"
     aria-labelledby="folderUploadModalTitle">
<div class="modal-card modal-card--wide" role="document">
<form action="{{ route($role . '.upload-document') }}" method="POST" enctype="multipart/form-data" id="folderUploadForm" data-custom-submit>
    @csrf
    <input type="hidden" name="folder_id" value="{{ $currentFolder->folder_id }}">
    <div class="modal-header">
        <div class="site-upload-modal__heading">
            <span class="site-upload-modal__icon" aria-hidden="true"><i class="fas fa-upload"></i></span>
            <div>
                <h2 class="modal-title" id="folderUploadModalTitle">Upload to {{ $currentFolder->folder_name }}</h2>
                <p class="site-upload-modal__subtitle">Add up to 3 documents to this folder.</p>
            </div>
        </div>
        <button type="button" class="modal-close" onclick="toggleFolderUpload(false)" aria-label="Close upload dialog">
            <i class="fas fa-times" aria-hidden="true"></i>
        </button>
    </div>
    <div class="modal-body">
    <div class="site-upload-grid">
        @if($useCourseSelect ?? false)
            @include('partials.ite-subject-picker', [
                'pickerId' => 'folderCoursePicker',
                'subjects' => \App\Support\IteSubjects::labelsForUser(auth()->user()),
            ])
            <div class="form-group">
                <label class="form-label">Document Title <span class="text-xs font-normal text-gray-500">(optional)</span></label>
                <input type="text" name="document_title" class="form-control" placeholder="Leave blank to use the uploaded file name" maxlength="{{ $docTitleMax }}" value="{{ old('document_title') }}">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">A course folder will be created from your selection if it does not exist yet.</p>
            </div>
        @elseif($useEqUploadLeaf ?? false)
            @php $eqHierarchy = app(\App\Services\AcademicHierarchyService::class); @endphp
            <div class="form-group md:col-span-2">
                <label class="form-label">Subject</label>
                <input type="text" class="form-control bg-gray-100 dark:bg-gray-800" value="{{ $eqHierarchy->subjectLabelFromEqUploadFolder($currentFolder) ?? '' }}" readonly>
            </div>
            <div class="form-group">
                <label class="form-label">Assessment</label>
                <input type="text" class="form-control bg-gray-100 dark:bg-gray-800" value="{{ $currentFolder->parent?->folder_name ?? '' }}" readonly>
            </div>
            <div class="form-group">
                <label class="form-label">Type</label>
                <input type="text" class="form-control bg-gray-100 dark:bg-gray-800" value="{{ $currentFolder->folder_name }}" readonly>
            </div>
            <div class="form-group">
                <label class="form-label">Document Title <span class="text-xs font-normal text-gray-500">(optional)</span></label>
                <input type="text" name="document_title" class="form-control" placeholder="Leave blank to use the uploaded file name" maxlength="{{ $docTitleMax }}" value="{{ old('document_title') }}">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Choose PDF or Word. Faculty uploads stay pending until the Dean approves them.</p>
            </div>
        @elseif($useTgUploadLeaf ?? false)
            <div class="form-group md:col-span-2">
                <label class="form-label">Subject</label>
                <input type="text" class="form-control bg-gray-100 dark:bg-gray-800" value="{{ app(\App\Services\AcademicHierarchyService::class)->subjectLabelFromTgUploadFolder($currentFolder) ?? ($currentFolder->parent?->folder_name ?? '') }}" readonly>
            </div>
            <div class="form-group">
                <label class="form-label">Document Title <span class="text-xs font-normal text-gray-500">(optional)</span></label>
                <input type="text" name="document_title" class="form-control" placeholder="Leave blank to use the uploaded file name" maxlength="{{ $docTitleMax }}" value="{{ old('document_title') }}">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Choose PDF or Word. Faculty uploads stay pending until the Dean approves them.</p>
            </div>
        @elseif($useCourseFolderUpload ?? false)
            <div class="form-group md:col-span-2">
                <label class="form-label">Course</label>
                <input type="text" class="form-control bg-gray-100 dark:bg-gray-800" value="{{ $currentFolder->folder_name }}" readonly>
            </div>
            <div class="form-group">
                <label class="form-label">Document Title <span class="text-xs font-normal text-gray-500">(optional)</span></label>
                <input type="text" name="document_title" class="form-control" placeholder="Leave blank to use the uploaded file name" maxlength="{{ $docTitleMax }}" value="{{ old('document_title') }}">
            </div>
        @else
            <div class="form-group">
                <label class="form-label">Document Title <span class="text-xs font-normal text-gray-500">(optional)</span></label>
                <input type="text" name="document_title" class="form-control" placeholder="Leave blank to use the uploaded file name" maxlength="{{ $docTitleMax }}" value="{{ old('document_title') }}">
            </div>
        @endif
        <div class="form-group">
            <label class="form-label">Document Type <span class="text-red-500">*</span></label>
            @php $pdfWordOnly = ($useEqUploadLeaf ?? false) || ($useTgUploadLeaf ?? false) || (($activeTab ?? '') === 'exam-questionnaires') || (($activeTab ?? '') === 'teaching-guides'); @endphp
            <select name="document_type" id="folderDocType" class="form-control" required>
                <option value="">Select Document Type</option>
                <option value="pdf" @selected(old('document_type') === 'pdf')>PDF</option>
                <option value="word" @selected(old('document_type') === 'word')>Word Document</option>
                @unless($pdfWordOnly)
                <option value="image" @selected(old('document_type') === 'image')>Image (JPG, PNG, GIF, WebP)</option>
                @endunless
            </select>
            @if($pdfWordOnly)
            <p class="text-xs text-amber-700 dark:text-amber-400 mt-1">
                <i class="fas fa-info-circle"></i> This folder accepts PDF or Word files only.
            </p>
            @endif
        </div>
        @if(($useItSubjectPicker ?? false) && !($useCourseSelect ?? false) && !($useCourseFolderUpload ?? false) && !($useTgUploadLeaf ?? false) && !($useEqUploadLeaf ?? false))
        @include('partials.ite-subject-picker', [
            'pickerId' => 'folderSubjectPicker',
            'subjects' => \App\Support\IteSubjects::labelsForUser(auth()->user()),
        ])
        @endif
        @if($activeTab === 'exam-questionnaires' && !($useEqUploadLeaf ?? false))
        <div class="form-group">
            <label class="form-label">Exam Type <span class="text-red-500">*</span></label>
            <select name="exam_type" class="form-control" required>
                <option value="">-- Select Exam Type --</option>
                @foreach(['Quiz','Prelim','Midterm','Pre-Final','Final'] as $type)
                    <option value="{{ $type }}" {{ old('exam_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                @endforeach
            </select>
        </div>
        @endif
        @if($shareableUploadTab ?? false)
        @include('partials.recipient-picker', ['pickerId' => 'folderRecipientPicker', 'role' => $role])
        @endif
        <div class="form-group">
            <label class="form-label">Choose Files * (Max 3)</label>
            <input type="file" name="documents[]" id="folderFileInput" class="form-control" multiple required disabled data-dropzone="1">
            <small id="folderFileHelp" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                <i class="fas fa-lock"></i> Select Document Type first &mdash; Max 10 MB per file
            </small>
            <p id="folderFileError" class="text-xs text-red-600 dark:text-red-400 mt-1 hidden" role="alert"></p>
        </div>
    </div>
    <div class="site-upload-note">
        <i class="fas fa-info-circle" aria-hidden="true"></i>
        <span>Files are uploaded to <strong>{{ $currentFolder->folder_name }}</strong> and recorded in your activity log.</span>
    </div>
    </div>
    <div class="modal-footer">
        <button type="button" onclick="toggleFolderUpload(false)" class="btn btn-secondary">
            Cancel
        </button>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-upload" aria-hidden="true"></i> <span>Upload Files</span>
        </button>
    </div>
</form>
</div>
</div>
