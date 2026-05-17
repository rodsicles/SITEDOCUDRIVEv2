{{-- Document Category Tabs + Folder Navigation --}}
@php
    $role = '';
    if (request()->routeIs('faculty.*')) $role = 'faculty';
    elseif (request()->routeIs('coordinator.*')) $role = 'coordinator';
    elseif (request()->routeIs('dean.*')) $role = 'dean';
    $docsRoute = $role . '.documents';
    $canUpload = in_array($role, ['faculty', 'coordinator', 'dean']);
    $documentViewer = auth()->user();
@endphp

<div class="content-card mb-6">
    {{-- Category Tabs --}}
    @php
        $validTabs = $folderTree->pluck('folder_name')->map(fn($n) => \Illuminate\Support\Str::slug($n))->toArray();
        $activeTab = in_array($tab, $validTabs) ? $tab : ($validTabs[0] ?? '');
        $shareableUploadTab = in_array($activeTab, ['teaching-guides', 'exam-questionnaires'], true)
            && $documentViewer && $documentViewer->canUploadSharedDocuments();
    @endphp
    <div class="category-tabs">
        @foreach($folderTree as $category)
            @php
                $tabSlug = \Illuminate\Support\Str::slug($category->folder_name);
            @endphp
            <a href="{{ route($docsRoute, ['tab' => $tabSlug]) }}"
               class="category-tab {{ $activeTab === $tabSlug ? 'active' : '' }}">
                <i class="fas {{ $tabSlug === 'academics' ? 'fa-book' : 'fa-certificate' }} mr-2"></i>
                {{ $category->folder_name }}
            </a>
        @endforeach
    </div>

    {{-- Breadcrumb Navigation (shown when inside a folder) --}}
    @if(isset($currentFolder) && $currentFolder)
    <div class="breadcrumb-nav">
        <a href="{{ route($docsRoute, ['tab' => $tab]) }}" class="breadcrumb-link">
            <i class="fas fa-home"></i> All
        </a>
        @foreach($breadcrumbs as $ancestor)
            <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
            <a href="{{ route($docsRoute, ['tab' => $tab, 'folder' => $ancestor->folder_id]) }}" class="breadcrumb-link">
                {{ $ancestor->folder_name }}
            </a>
        @endforeach
        <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
        <span class="breadcrumb-current">{{ $currentFolder->folder_name }}</span>
    </div>
    @endif

    {{-- Folder Cards OR Leaf Folder Content --}}
    @php
        $displayFolders = collect();
        $isLeafFolder = false;
        if (isset($currentFolder) && $currentFolder) {
            $displayFolders = $currentFolder->children()->system()->orderBy('sort_order')
                ->withCount(['documents' => fn ($q) => $q->visibleTo($documentViewer)])
                ->get();
            $isLeafFolder = $displayFolders->isEmpty();
        } else {
            foreach ($folderTree as $category) {
                $tabSlug = \Illuminate\Support\Str::slug($category->folder_name);
                if ($activeTab === $tabSlug) {
                    $displayFolders = $category->children;
                    break;
                }
            }
        }
    @endphp

    @if($isLeafFolder)
        {{-- LEAF FOLDER: Show upload button + documents --}}
        <div class="px-6 py-4">
            <div class="flex items-center justify-between mb-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <i class="fas fa-folder-open mr-1"></i> {{ $documents->total() }} document(s) in this folder
                </div>
                @if($canUpload)
                <div class="flex gap-2">
                    @if(isset($isPrcFolder) && $isPrcFolder)
                    <button type="button" id="btnPrcForm" onclick="togglePrcForm()" class="btn btn-primary doc-action-btn" aria-pressed="false">
                        <i class="fas fa-clipboard-list mr-1"></i> Record Exam Results
                    </button>
                    @endif
                    @if(isset($isCertFolder) && $isCertFolder)
                    <button type="button" id="btnCertForm" onclick="toggleCertForm()" class="btn btn-primary doc-action-btn" aria-pressed="false">
                        <i class="fas fa-plus-circle mr-1"></i> Record Passers
                    </button>
                    @endif
                    <button type="button" id="btnCreateFolder" onclick="toggleCreateSubfolder()" class="btn btn-primary doc-action-btn" aria-pressed="false">
                        <i class="fas fa-folder-plus mr-1"></i> Create Folder
                    </button>
                    <button type="button" id="btnFolderUpload" onclick="toggleFolderUpload()" class="btn btn-success doc-action-btn" aria-pressed="false">
                        <i class="fas fa-upload mr-1"></i> Upload to this Folder
                    </button>
                </div>
                @endif
            </div>

            {{-- Search inside folder --}}
            <div class="mb-4">
                <form action="{{ route($docsRoute) }}" method="GET" class="flex gap-2 items-center">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <input type="hidden" name="folder" value="{{ $currentFolder->folder_id }}">
                    <div class="flex-1 relative">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control text-sm pl-9" placeholder="Search documents in this folder...">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    </div>
                    <button type="submit" class="btn btn-primary text-sm">
                        <i class="fas fa-search"></i> Search
                    </button>
                    @if(request('search'))
                    <a href="{{ route($docsRoute, ['tab' => $tab, 'folder' => $currentFolder->folder_id]) }}" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm">
                        <i class="fas fa-times"></i> Clear
                    </a>
                    @endif
                </form>
            </div>

            {{-- Create Subfolder Form --}}
            @if($canUpload)
            <form id="createSubfolderForm" class="hidden mb-4" style="border: 1px solid #e0e0e0; padding: 16px; background: #f9fafb;" onsubmit="return false;">
                @csrf
                <input type="hidden" name="parent_id" value="{{ $currentFolder->folder_id }}">
                <h4 class="text-sm font-bold mb-3 text-gray-700 dark:text-gray-300">
                    <i class="fas fa-folder-plus mr-1"></i> Create Subfolder in "{{ $currentFolder->folder_name }}"
                </h4>
                <div class="flex gap-3 items-end">
                    <div class="form-group mb-0 flex-1">
                        <label class="form-label">Folder Name *</label>
                        <input type="text" name="folder_name" class="form-control" placeholder="Enter folder name" required maxlength="13">
                    </div>
                    <button type="button" onclick="submitCreateSubfolder()" class="btn btn-primary" id="subfolderSubmitBtn">
                        <i class="fas fa-plus"></i> Create
                    </button>
                    <button type="button" onclick="toggleCreateSubfolder()" class="btn btn-sm bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                        Cancel
                    </button>
                </div>
            </form>
            @endif

            {{-- PRC Exam Results Form --}}
            @if($canUpload && isset($isPrcFolder) && $isPrcFolder)
            <form id="prcExamForm" class="hidden mb-4 exam-form" onsubmit="return false;">
                @csrf
                <input type="hidden" name="folder_slug" value="{{ $currentFolder->slug }}">
                <h4 class="text-sm font-bold mb-3 text-gray-700 dark:text-gray-300">
                    <i class="fas fa-clipboard-list mr-1"></i> PRC Board Exam Results
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Batch / Period *</label>
                        <input type="text" name="batch_label" class="form-control" placeholder="e.g. March 2025" required maxlength="50">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Civil Engineer — Passed *</label>
                        <input type="number" name="ce_passed" class="form-control" min="0" required placeholder="0" id="prcCePassed">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Civil Engineer — Total Examinees</label>
                        <input type="number" name="ce_total" class="form-control" min="0" placeholder="Optional">
                    </div>
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Civil Engineer — Passer Names</label>
                        <textarea name="ce_names" class="form-control" rows="4" placeholder="Enter names, one per line" oninput="updatePasserCount(this, 'prcCePassed')"></textarea>
                        <small class="text-xs text-gray-500 dark:text-gray-400 mt-1">One name per line. Passed count auto-updates from names entered.</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Environmental Sanitary Eng. — Passed *</label>
                        <input type="number" name="ese_passed" class="form-control" min="0" required placeholder="0" id="prcEsePassed">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Environmental Sanitary Eng. — Total Examinees</label>
                        <input type="number" name="ese_total" class="form-control" min="0" placeholder="Optional">
                    </div>
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Environmental Sanitary Eng. — Passer Names</label>
                        <textarea name="ese_names" class="form-control" rows="4" placeholder="Enter names, one per line" oninput="updatePasserCount(this, 'prcEsePassed')"></textarea>
                        <small class="text-xs text-gray-500 dark:text-gray-400 mt-1">One name per line. Passed count auto-updates from names entered.</small>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="submitPrcForm()" class="btn btn-primary" id="prcSubmitBtn">
                        <i class="fas fa-file-word"></i> Save & Generate Document
                    </button>
                    <button type="button" onclick="togglePrcForm()" class="btn btn-sm bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                        Cancel
                    </button>
                </div>
            </form>
            @endif

            {{-- Certification Count Form --}}
            @if($canUpload && isset($isCertFolder) && $isCertFolder)
            <form id="certCountForm" class="hidden mb-4 exam-form" onsubmit="return false;">
                @csrf
                <input type="hidden" name="folder_slug" value="{{ $currentFolder->slug }}">
                <input type="hidden" name="folder_id" value="{{ $currentFolder->folder_id }}">
                <h4 class="text-sm font-bold mb-3 text-gray-700 dark:text-gray-300">
                    <i class="fas fa-certificate mr-1"></i> Record {{ $currentFolder->folder_name }} Passers
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="form-group">
                        <label class="form-label">Year / Period *</label>
                        <input type="text" name="batch_label" class="form-control" placeholder="e.g. 2025" required maxlength="50">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Number of Passers *</label>
                        <input type="number" name="passed_count" class="form-control" min="0" required placeholder="0" id="certPassedCount">
                    </div>
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Passer Names</label>
                        <textarea name="passer_names" class="form-control" rows="4" placeholder="Enter names, one per line" oninput="updatePasserCount(this, 'certPassedCount')"></textarea>
                        <small class="text-xs text-gray-500 dark:text-gray-400 mt-1">One name per line. Passed count auto-updates from names entered.</small>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="submitCertForm()" class="btn btn-primary" id="certSubmitBtn">
                        <i class="fas fa-save"></i> Save Count
                    </button>
                    <button type="button" onclick="toggleCertForm()" class="btn btn-sm bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                        Cancel
                    </button>
                </div>
            </form>
            @endif

            {{-- Exam Records Summary Table --}}
            @if((isset($isPrcFolder) && $isPrcFolder) || (isset($isCertFolder) && $isCertFolder))
                @if(isset($examRecords) && $examRecords->count() > 0)
                <div class="mb-4">
                    <h4 class="text-sm font-bold mb-2 text-gray-700 dark:text-gray-300">
                        <i class="fas fa-history mr-1"></i> Past Records
                    </h4>
                    <table class="exam-summary-table">
                        <thead>
                            <tr>
                                @if(isset($isPrcFolder) && $isPrcFolder)
                                <th>Batch</th>
                                <th>Exam Type</th>
                                <th>Passed</th>
                                <th>Total</th>
                                <th>Names</th>
                                <th>Recorded</th>
                                @else
                                <th>Year</th>
                                <th>Passers</th>
                                <th>Names</th>
                                <th>Recorded By</th>
                                <th>Date</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($examRecords as $record)
                            <tr>
                                @if(isset($isPrcFolder) && $isPrcFolder)
                                <td>{{ $record->batch_label }}</td>
                                <td>{{ $record->exam_type }}</td>
                                <td><strong>{{ $record->passed_count }}</strong></td>
                                <td>{{ $record->total_examinees ?? '—' }}</td>
                                <td>
                                    @if($record->passer_names && count($record->passer_names) > 0)
                                    <button type="button" onclick="togglePasserNames(this)" class="btn btn-sm text-xs" style="padding: 2px 8px; background: rgba(2,138,15,0.1); color: #028a0f;">
                                        <i class="fas fa-chevron-down"></i> {{ count($record->passer_names) }} names
                                    </button>
                                    @else
                                    <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td>{{ $record->created_at->format('M d, Y') }}</td>
                                @else
                                <td>{{ $record->batch_label }}</td>
                                <td><strong>{{ $record->passed_count }}</strong></td>
                                <td>
                                    @if($record->passer_names && count($record->passer_names) > 0)
                                    <button type="button" onclick="togglePasserNames(this)" class="btn btn-sm text-xs" style="padding: 2px 8px; background: rgba(2,138,15,0.1); color: #028a0f;">
                                        <i class="fas fa-chevron-down"></i> {{ count($record->passer_names) }} names
                                    </button>
                                    @else
                                    <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td>{{ $record->recorder->employee->full_name ?? $record->recorder->username }}</td>
                                <td>{{ $record->created_at->format('M d, Y') }}</td>
                                @endif
                            </tr>
                            @if($record->passer_names && count($record->passer_names) > 0)
                            <tr class="passer-names-row hidden">
                                <td colspan="{{ (isset($isPrcFolder) && $isPrcFolder) ? 6 : 5 }}" style="background: #f9fafb; padding: 8px 16px;">
                                    <div class="text-xs text-gray-600 dark:text-gray-400">
                                        <strong>Passer Names:</strong>
                                        <ol style="margin: 4px 0 0 16px; padding: 0;">
                                            @foreach($record->passer_names as $name)
                                            <li>{{ $name }}</li>
                                            @endforeach
                                        </ol>
                                    </div>
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            @endif

            @if($canUpload)
            <form action="{{ route($role . '.upload-document') }}" method="POST" enctype="multipart/form-data" id="folderUploadForm" class="hidden mb-4" style="border: 1px solid #e0e0e0; padding: 16px; background: #f9fafb;">
                @csrf
                <input type="hidden" name="folder_id" value="{{ $currentFolder->folder_id }}">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="form-group">
                        <label class="form-label">Document Title *</label>
                        <input type="text" name="document_title" class="form-control" placeholder="Enter document title" required maxlength="13">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Document Type *</label>
                        <select name="document_type" id="folderDocType" class="form-control" required>
                            <option value="">Select Document Type</option>
                            <option value="pdf">PDF Document</option>
                            <option value="word">Word Document</option>
                            <option value="image">Image File</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tags (max 15 characters)</label>
                        <input type="text" name="tags" id="folderTagsInput" class="form-control" placeholder="e.g. urgent" maxlength="15">
                    </div>
                    @if($shareableUploadTab)
                    <div class="form-group">
                        <label class="form-label">Subject (for Teaching Guides list)</label>
                        <input type="text" name="subject" class="form-control" placeholder="e.g. IT 101" maxlength="100">
                    </div>
                    @include('partials.recipient-picker', ['pickerId' => 'folderRecipientPicker', 'role' => $role])
                    @endif
                    <div class="form-group">
                        <label class="form-label">Choose Files * (Max 3)</label>
                        <input type="file" name="documents[]" id="folderFileInput" class="form-control" multiple required disabled data-dropzone="1">
                        <small id="folderFileHelp" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <i class="fas fa-lock"></i> Select Document Type first
                        </small>
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
            @endif
        </div>
    @else
        {{-- FOLDER CARDS --}}
        <div class="folder-container-new px-6 py-4 flex gap-3 flex-wrap">
            @forelse($displayFolders as $folder)
            <div class="folder-card-new {{ (isset($folderFilter) && $folderFilter == $folder->folder_id) ? 'folder-card-active' : '' }}">
                <a href="{{ route($docsRoute, ['tab' => $tab, 'folder' => $folder->folder_id]) }}" class="folder-card-link-new">
                    <div class="folder-icon-new" style="background-color: #028a0f; color: white;">
                        <i class="fas fa-folder"></i>
                    </div>
                    <div class="folder-info-new">
                        <div class="folder-name-new">{{ $folder->folder_name }}</div>
                        <div class="folder-count-new">
                            {{ $folder->documents_count }} Files
                            @if($folder->children->count() > 0)
                                <i class="fas fa-chevron-right ml-1" style="font-size: 0.65rem;"></i>
                            @endif
                        </div>
                    </div>
                </a>
                <div class="folder-actions-new">
                    <a href="{{ route($docsRoute, ['tab' => $tab, 'folder' => $folder->folder_id]) }}" class="folder-action-btn" title="Search in {{ $folder->folder_name }}">
                        <i class="fas fa-search"></i>
                    </a>
                </div>
            </div>
            @empty
            <div class="empty-state p-8 text-center w-full">
                <div class="empty-state-icon mb-3 text-4xl text-gray-300 dark:text-gray-600">
                    <i class="fas fa-folder-open"></i>
                </div>
                <div class="empty-state-text text-gray-600 dark:text-gray-400">No folders in this category.</div>
            </div>
            @endforelse
        </div>
    @endif
</div>

@if(isset($isLeafFolder) && $isLeafFolder && $canUpload)
@push('scripts')
<script>
    function syncDocActionBtn(btnId, formId) {
        const btn = document.getElementById(btnId);
        const form = document.getElementById(formId);
        if (!btn || !form) return;
        const isOpen = !form.classList.contains('hidden');
        btn.classList.toggle('is-active', isOpen);
        btn.setAttribute('aria-pressed', isOpen ? 'true' : 'false');
    }

    function toggleFolderUpload() {
        const form = document.getElementById('folderUploadForm');
        if (!form) return;
        form.classList.toggle('hidden');
        syncDocActionBtn('btnFolderUpload', 'folderUploadForm');
    }

    function toggleCreateSubfolder() {
        const form = document.getElementById('createSubfolderForm');
        if (!form) return;
        form.classList.toggle('hidden');
        syncDocActionBtn('btnCreateFolder', 'createSubfolderForm');
    }

    async function submitCreateSubfolder() {
        const form = document.getElementById('createSubfolderForm');
        const btn = document.getElementById('subfolderSubmitBtn');
        const originalText = btn.innerHTML;
        const folderName = form.querySelector('[name="folder_name"]').value.trim();
        const parentId = form.querySelector('[name="parent_id"]').value;

        if (!folderName) { alert('Please enter a folder name.'); return; }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';

        try {
            const response = await fetch("{{ route($role . '.folders.store') }}", {
                method: 'POST',
                body: JSON.stringify({ folder_name: folderName, parent_id: parseInt(parentId) }),
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                }
            });

            const data = await response.json();
            if (response.ok && data.success) {
                showToast(data.message || 'Folder created!', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                const errors = data.errors ? Object.values(data.errors).flat().join(', ') : (data.message || 'Failed to create folder.');
                showToast(errors, 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        } catch (error) {
            showToast('Error creating folder. Please try again.', 'error');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }

    function togglePrcForm() {
        const form = document.getElementById('prcExamForm');
        if (!form) return;
        form.classList.toggle('hidden');
        syncDocActionBtn('btnPrcForm', 'prcExamForm');
    }

    function toggleCertForm() {
        const form = document.getElementById('certCountForm');
        if (!form) return;
        form.classList.toggle('hidden');
        syncDocActionBtn('btnCertForm', 'certCountForm');
    }

    function updatePasserCount(textarea, countFieldId) {
        const names = textarea.value.split('\n').filter(n => n.trim() !== '');
        const countField = document.getElementById(countFieldId);
        if (countField) countField.value = names.length;
    }

    function parseNames(text) {
        return text.split('\n').map(n => n.trim()).filter(n => n !== '');
    }

    function togglePasserNames(btn) {
        const row = btn.closest('tr');
        const namesRow = row.nextElementSibling;
        if (namesRow && namesRow.classList.contains('passer-names-row')) {
            namesRow.classList.toggle('hidden');
            btn.querySelector('i').classList.toggle('fa-chevron-down');
            btn.querySelector('i').classList.toggle('fa-chevron-up');
        }
    }

    document.getElementById('folderDocType')?.addEventListener('change', function() {
        const fileInput = document.getElementById('folderFileInput');
        const fileHelp = document.getElementById('folderFileHelp');
        const type = this.value;

        if (!type) {
            fileInput.disabled = true;
            fileInput.value = '';
            fileInput.removeAttribute('accept');
            fileHelp.innerHTML = '<i class="fas fa-lock"></i> Select Document Type first';
        } else if (type === 'pdf') {
            fileInput.disabled = false;
            fileInput.setAttribute('accept', '.pdf');
            fileHelp.innerHTML = '<i class="fas fa-file-pdf"></i> PDF files only (Max: 10MB)';
        } else if (type === 'word') {
            fileInput.disabled = false;
            fileInput.setAttribute('accept', '.doc,.docx');
            fileHelp.innerHTML = '<i class="fas fa-file-word"></i> DOC, DOCX only (Max: 10MB)';
        } else if (type === 'image') {
            fileInput.disabled = false;
            fileInput.setAttribute('accept', '.jpg,.jpeg,.png');
            fileHelp.innerHTML = '<i class="fas fa-file-image"></i> JPG, PNG only (Max: 10MB)';
        }
    });

    document.getElementById('folderUploadForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const docType = document.getElementById('folderDocType').value;
        const fileInput = document.getElementById('folderFileInput');

        if (!docType) { alert('Please select a Document Type first!'); return; }
        if (fileInput.files.length === 0) { alert('Please select at least one file!'); return; }
        if (fileInput.files.length > 3) { alert('Maximum 3 files per upload.'); return; }

        const files = fileInput.files;
        for (let i = 0; i < files.length; i++) {
            const ext = files[i].name.toLowerCase().split('.').pop();
            if (docType === 'pdf' && ext !== 'pdf') { alert('File ' + files[i].name + ' is not a PDF.'); return; }
            if (docType === 'word' && !['doc','docx'].includes(ext)) { alert('File ' + files[i].name + ' is not a Word document.'); return; }
            if (docType === 'image' && !['jpg','jpeg','png'].includes(ext)) { alert('File ' + files[i].name + ' is not an image.'); return; }
        }

        const tagsVal = document.getElementById('folderTagsInput')?.value || '';
        if (tagsVal.trim().length > 15) { alert('Tags max 15 characters.'); return; }

        @if($shareableUploadTab)
        if (typeof folderRecipientPickerValidate === 'function' && !folderRecipientPickerValidate()) {
            return;
        }
        @endif

        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';

        try {
            const formData = new FormData(this);
            const response = await fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            if (response.status === 429) {
                showToast('Upload limit reached! Try again later.', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                return;
            }

            const contentType = response.headers.get('content-type') || '';
            let data = {};
            if (contentType.includes('application/json')) {
                data = await response.json();
            } else {
                const text = await response.text();
                console.error('Upload non-JSON response', response.status, text.slice(0, 500));
                showToast('Upload failed (server returned ' + response.status + '). Check browser console or application logs.', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                return;
            }

            if (response.ok && data.success !== false) {
                showToast(data.message || 'Uploaded successfully!', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                let msg = data.message || 'Upload failed';
                if (data.errors) {
                    const flat = Object.values(data.errors).flat();
                    if (flat.length) msg = flat.join(', ');
                }
                if (data.debug) {
                    console.error('Upload debug:', data.debug);
                    msg += ' (see console for details)';
                }
                console.error('Upload failed', response.status, data);
                showToast(msg, 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        } catch (error) {
            console.error('Upload request error', error);
            showToast('Upload error: ' + (error.message || 'network or server failure'), 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });

    async function submitPrcForm() {
        const form = document.getElementById('prcExamForm');
        const btn = document.getElementById('prcSubmitBtn');
        const originalText = btn.innerHTML;

        const batchLabel = form.querySelector('[name="batch_label"]').value.trim();
        const cePassed = form.querySelector('[name="ce_passed"]').value;
        const esePassed = form.querySelector('[name="ese_passed"]').value;

        if (!batchLabel) { alert('Please enter a Batch / Period.'); return; }
        if (cePassed === '' || parseInt(cePassed) < 0) { alert('Please enter Civil Engineer passers count.'); return; }
        if (esePassed === '' || parseInt(esePassed) < 0) { alert('Please enter Environmental Sanitary Eng. passers count.'); return; }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';

        try {
            const formData = new FormData(form);
            const ceNamesTextarea = form.querySelector('[name="ce_names"]');
            const eseNamesTextarea = form.querySelector('[name="ese_names"]');
            if (ceNamesTextarea) formData.set('ce_names', ceNamesTextarea.value);
            if (eseNamesTextarea) formData.set('ese_names', eseNamesTextarea.value);

            const response = await fetch("{{ route($role . '.store-exam-record') }}", {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            });

            const data = await response.json();
            if (response.ok && data.success) {
                showToast(data.message || 'Exam results recorded!', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                const errors = data.errors ? Object.values(data.errors).flat().join(', ') : (data.message || 'Failed to save.');
                showToast(errors, 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        } catch (error) {
            showToast('Error saving exam results. Please try again.', 'error');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }

    async function submitCertForm() {
        const form = document.getElementById('certCountForm');
        const btn = document.getElementById('certSubmitBtn');
        const originalText = btn.innerHTML;

        const batchLabel = form.querySelector('[name="batch_label"]').value.trim();
        const passedCount = form.querySelector('[name="passed_count"]').value;

        if (!batchLabel) { alert('Please enter a Year / Period.'); return; }
        if (passedCount === '' || parseInt(passedCount) < 0) { alert('Please enter the number of passers.'); return; }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        try {
            const formData = new FormData(form);
            const namesTextarea = form.querySelector('[name="passer_names"]');
            if (namesTextarea) formData.set('passer_names', namesTextarea.value);

            const response = await fetch("{{ route($role . '.store-exam-record') }}", {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            });

            const data = await response.json();
            if (response.ok && data.success) {
                showToast(data.message || 'Passer count recorded!', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                const errors = data.errors ? Object.values(data.errors).flat().join(', ') : (data.message || 'Failed to save.');
                showToast(errors, 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        } catch (error) {
            showToast('Error saving count. Please try again.', 'error');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }
</script>
@endpush
@endif
