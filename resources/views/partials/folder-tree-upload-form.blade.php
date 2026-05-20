@php
    $docTitleMax = \App\Support\DocumentNaming::TITLE_MAX_LENGTH;
@endphp
<form action="{{ route($role . '.upload-document') }}" method="POST" enctype="multipart/form-data" id="folderUploadForm" data-custom-submit class="hidden mb-4 overflow-visible" style="border: 1px solid #e0e0e0; padding: 16px; background: #f9fafb;">
    @csrf
    <input type="hidden" name="folder_id" value="{{ $currentFolder->folder_id }}">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        @if($useCourseSelect ?? false)
            @include('partials.ite-subject-picker', [
                'pickerId' => 'folderCoursePicker',
                'subjects' => \App\Support\IteSubjects::labelsForUser(auth()->user()),
            ])
            <div class="form-group">
                <label class="form-label">File Name <span class="text-red-500">*</span></label>
                <input type="text" name="document_title" class="form-control" placeholder="Enter file name" required maxlength="{{ $docTitleMax }}" value="{{ old('document_title') }}">
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
                <label class="form-label">File Name <span class="text-red-500">*</span></label>
                <input type="text" name="document_title" class="form-control" placeholder="Enter file name" required maxlength="{{ $docTitleMax }}" value="{{ old('document_title') }}">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Choose PDF or Word. Faculty uploads stay pending until the Dean approves them.</p>
            </div>
        @elseif($useTgUploadLeaf ?? false)
            <div class="form-group md:col-span-2">
                <label class="form-label">Subject</label>
                <input type="text" class="form-control bg-gray-100 dark:bg-gray-800" value="{{ app(\App\Services\AcademicHierarchyService::class)->subjectLabelFromTgUploadFolder($currentFolder) ?? ($currentFolder->parent?->folder_name ?? '') }}" readonly>
            </div>
            <div class="form-group">
                <label class="form-label">File Name <span class="text-red-500">*</span></label>
                <input type="text" name="document_title" class="form-control" placeholder="Enter file name" required maxlength="{{ $docTitleMax }}" value="{{ old('document_title') }}">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Choose PDF or Word. Faculty uploads stay pending until the Dean approves them.</p>
            </div>
        @elseif($useCourseFolderUpload ?? false)
            <div class="form-group md:col-span-2">
                <label class="form-label">Course</label>
                <input type="text" class="form-control bg-gray-100 dark:bg-gray-800" value="{{ $currentFolder->folder_name }}" readonly>
            </div>
            <div class="form-group">
                <label class="form-label">File Name <span class="text-red-500">*</span></label>
                <input type="text" name="document_title" class="form-control" placeholder="Enter file name" required maxlength="{{ $docTitleMax }}" value="{{ old('document_title') }}">
            </div>
        @else
            <div class="form-group">
                <label class="form-label">File Name <span class="text-red-500">*</span></label>
                <input type="text" name="document_title" class="form-control" placeholder="Enter file name" required maxlength="{{ $docTitleMax }}" value="{{ old('document_title') }}">
            </div>
        @endif
        <div class="form-group">
            <label class="form-label">Document Type <span class="text-red-500">*</span></label>
            <select name="document_type" id="folderDocType" class="form-control" required>
                <option value="">Select Document Type</option>
                <option value="pdf" @selected(old('document_type') === 'pdf')>PDF</option>
                <option value="word" @selected(old('document_type') === 'word')>Word</option>
            </select>
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
                <i class="fas fa-lock"></i> Select Document Type first
            </small>
            <p id="folderFileError" class="text-xs text-red-600 dark:text-red-400 mt-1 hidden" role="alert"></p>
        </div>
    </div>
    <div class="flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-upload"></i> Upload
        </button>
        <button type="button" onclick="toggleFolderUpload()" class="btn btn-sm bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
            Cancel
        </button>
    </div>
</form>
