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
        $shareableCategoryTab = in_array($activeTab, ['teaching-guides', 'exam-questionnaires'], true);
        $shareableUploadTab = $shareableCategoryTab
            && $documentViewer && $documentViewer->canUploadSharedDocuments();
        $useItSubjectPicker = \App\Support\IteSubjects::shouldUseSubjectPicker($documentViewer, $shareableCategoryTab);
        $academicHierarchy = app(\App\Services\AcademicHierarchyService::class);
        $useCourseSelect = false;
        $customFoldersCategory = $folderTree->firstWhere('slug', \App\Models\Folder::CUSTOM_FOLDERS_SLUG);
        $isCustomFoldersTab = $activeTab === 'custom-folders';
    @endphp
    <div class="category-tabs">
        @foreach($folderTree as $category)
            @php
                $tabSlug = \Illuminate\Support\Str::slug($category->folder_name);
                $tabIcon = match ($tabSlug) {
                    'academics' => 'fa-book',
                    'custom-folders' => 'fa-folder-plus',
                    default => 'fa-certificate',
                };
            @endphp
            <a href="{{ route($docsRoute, ['tab' => $tabSlug]) }}"
               class="category-tab {{ $activeTab === $tabSlug ? 'active' : '' }}">
                <i class="fas {{ $tabIcon }} mr-2"></i>
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
        $folderService = app(\App\Services\FolderService::class);
        if (isset($currentFolder) && $currentFolder) {
            $displayFolders = $folderService->getDisplayFolders($currentFolder, $documentViewer);
            $isLeafFolder = $displayFolders->isEmpty();
        } else {
            foreach ($folderTree as $category) {
                $tabSlug = \Illuminate\Support\Str::slug($category->folder_name);
                if ($activeTab === $tabSlug) {
                    $displayFolders = $category->children;
                    break;
                }
            }
            if ($displayFolders->isNotEmpty()) {
                $displayFolders = $folderService->attachSubtreeDocumentCounts($displayFolders, $documentViewer);
            }
        }

        $isTypeLeafFolder = isset($currentFolder)
            && $currentFolder
            && $shareableCategoryTab
            && $academicHierarchy->isSemesterTypeLeafFolder($currentFolder);
        $isCourseFolder = isset($currentFolder)
            && $currentFolder
            && $academicHierarchy->isCourseSubfolder($currentFolder);
        if (isset($currentFolder) && $currentFolder) {
            $isCourseFolder = $isCourseFolder || $academicHierarchy->isCourseSubfolder($currentFolder);
            $isTypeLeafFolder = $isTypeLeafFolder && !$isCourseFolder;
            $isLeafFolder = $isCourseFolder || (!$isTypeLeafFolder && $displayFolders->isEmpty());
        }
        $isTgSemesterFolder = isset($currentFolder)
            && $currentFolder
            && $activeTab === 'teaching-guides'
            && $academicHierarchy->isTgSemesterFolder($currentFolder);
        $isTgSubjectFolder = isset($currentFolder)
            && $currentFolder
            && $academicHierarchy->isTgSubjectFolder($currentFolder);
        $isTgUploadLeaf = isset($currentFolder)
            && $currentFolder
            && $academicHierarchy->isTgUploadLeafFolder($currentFolder);

        $isEqSemesterFolder = isset($currentFolder)
            && $currentFolder
            && $activeTab === 'exam-questionnaires'
            && $academicHierarchy->isEqSemesterFolder($currentFolder);
        $isEqSubjectFolder = isset($currentFolder)
            && $currentFolder
            && $academicHierarchy->isEqSubjectFolder($currentFolder);
        $isEqAssessmentFolder = isset($currentFolder)
            && $currentFolder
            && $academicHierarchy->isEqAssessmentFolder($currentFolder);
        $isEqUploadLeaf = isset($currentFolder)
            && $currentFolder
            && $academicHierarchy->isEqUploadLeafFolder($currentFolder);

        if (isset($currentFolder) && $currentFolder && $activeTab === 'teaching-guides') {
            if ($isTgUploadLeaf) {
                $isLeafFolder = true;
            } elseif ($isTgSubjectFolder) {
                $isLeafFolder = false;
            } elseif ($isTgSemesterFolder) {
                $isLeafFolder = false;
            }
        }

        if (isset($currentFolder) && $currentFolder && $activeTab === 'exam-questionnaires') {
            if ($isEqUploadLeaf) {
                $isLeafFolder = true;
            } elseif ($isEqAssessmentFolder) {
                $isLeafFolder = false;
            } elseif ($isEqSubjectFolder) {
                $isLeafFolder = false;
            } elseif ($isEqSemesterFolder) {
                $isLeafFolder = false;
            }
        }

        $useCourseSelect = false;
        $useCourseFolderUpload = false;
        $useTgUploadLeaf = $isTgUploadLeaf && $shareableCategoryTab;
        $useEqUploadLeaf = $isEqUploadLeaf && $shareableCategoryTab;
        $isCustomSubfolder = isset($currentFolder) && $currentFolder && $currentFolder->isCustomSubfolder();
        if ($isCustomSubfolder) {
            $isLeafFolder = true;
            $displayFolders = collect();
        }
    @endphp

    @if($isTgSemesterFolder)
        {{-- TG: Semester → pick subject → auto-create Subject/TG/LB --}}
        <div class="px-6 py-4">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                <i class="fas fa-graduation-cap mr-1"></i>
                Select a subject to open its folder (TG and LB are created automatically).
            </div>

            @include('partials.tg-semester-subject-form')

            <div class="folder-container-new flex gap-3 flex-wrap mt-4">
                @forelse($displayFolders as $folder)
                <div class="folder-card-new">
                    <a href="{{ route($docsRoute, ['tab' => $tab, 'folder' => $folder->folder_id]) }}" class="folder-card-link-new">
                        <div class="folder-icon-new" style="background-color: #028a0f; color: white;">
                            <i class="fas fa-folder"></i>
                        </div>
                        <div class="folder-info-new">
                            <div class="folder-name-new">{{ $folder->folder_name }}</div>
                            <div class="folder-count-new">{{ $folder->documents_count }} Files</div>
                        </div>
                    </a>
                </div>
                @empty
                <p class="text-sm text-gray-500 dark:text-gray-400 w-full py-4 text-center">
                    No subject folders yet. Choose a subject above to get started.
                </p>
                @endforelse
            </div>
        </div>
    @elseif($isTgSubjectFolder)
        {{-- TG: Subject folder → TG and LB only --}}
        <div class="px-6 py-4">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                <i class="fas fa-book mr-1"></i> {{ $currentFolder->folder_name }} — open <strong>TG</strong> or <strong>LB</strong> to upload files.
            </div>
            <div class="folder-container-new flex gap-3 flex-wrap">
                @forelse($displayFolders as $folder)
                <div class="folder-card-new">
                    <a href="{{ route($docsRoute, ['tab' => $tab, 'folder' => $folder->folder_id]) }}" class="folder-card-link-new">
                        <div class="folder-icon-new" style="background-color: #028a0f; color: white;">
                            <i class="fas fa-folder"></i>
                        </div>
                        <div class="folder-info-new">
                            <div class="folder-name-new">{{ $folder->folder_name }}</div>
                            <div class="folder-count-new">{{ $folder->documents_count }} Files</div>
                        </div>
                    </a>
                </div>
                @empty
                <p class="text-sm text-gray-500 dark:text-gray-400 w-full py-4 text-center">TG and LB folders are being prepared.</p>
                @endforelse
            </div>
        </div>
    @elseif($isEqSemesterFolder)
        <div class="px-6 py-4">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                <i class="fas fa-graduation-cap mr-1"></i>
                Select a subject to open its folder (Prelims, Midterms, Finals, and TOS/TOQ are created automatically).
            </div>
            @include('partials.eq-semester-subject-form')
            <div class="folder-container-new flex gap-3 flex-wrap mt-4">
                @forelse($displayFolders as $folder)
                <div class="folder-card-new">
                    <a href="{{ route($docsRoute, ['tab' => $tab, 'folder' => $folder->folder_id]) }}" class="folder-card-link-new">
                        <div class="folder-icon-new" style="background-color: #028a0f; color: white;"><i class="fas fa-folder"></i></div>
                        <div class="folder-info-new">
                            <div class="folder-name-new">{{ $folder->folder_name }}</div>
                            <div class="folder-count-new">{{ $folder->documents_count }} Files</div>
                        </div>
                    </a>
                </div>
                @empty
                <p class="text-sm text-gray-500 dark:text-gray-400 w-full py-4 text-center">No subject folders yet. Choose a subject above to get started.</p>
                @endforelse
            </div>
        </div>
    @elseif($isEqSubjectFolder)
        <div class="px-6 py-4">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                <i class="fas fa-book mr-1"></i> {{ $currentFolder->folder_name }} — open <strong>Prelims</strong>, <strong>Midterms</strong>, or <strong>Finals</strong>.
            </div>
            <div class="folder-container-new flex gap-3 flex-wrap">
                @forelse($displayFolders as $folder)
                <div class="folder-card-new">
                    <a href="{{ route($docsRoute, ['tab' => $tab, 'folder' => $folder->folder_id]) }}" class="folder-card-link-new">
                        <div class="folder-icon-new" style="background-color: #028a0f; color: white;"><i class="fas fa-folder"></i></div>
                        <div class="folder-info-new">
                            <div class="folder-name-new">{{ $folder->folder_name }}</div>
                            <div class="folder-count-new">{{ $folder->documents_count }} Files</div>
                        </div>
                    </a>
                </div>
                @empty
                <p class="text-sm text-gray-500 dark:text-gray-400 w-full py-4 text-center">Assessment folders are being prepared.</p>
                @endforelse
            </div>
        </div>
    @elseif($isEqAssessmentFolder)
        <div class="px-6 py-4">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                <i class="fas fa-layer-group mr-1"></i> {{ $currentFolder->folder_name }} — open <strong>TOS</strong> or <strong>TOQ</strong> to upload files.
            </div>
            <div class="folder-container-new flex gap-3 flex-wrap">
                @forelse($displayFolders as $folder)
                <div class="folder-card-new">
                    <a href="{{ route($docsRoute, ['tab' => $tab, 'folder' => $folder->folder_id]) }}" class="folder-card-link-new">
                        <div class="folder-icon-new" style="background-color: #028a0f; color: white;"><i class="fas fa-folder"></i></div>
                        <div class="folder-info-new">
                            <div class="folder-name-new">{{ $folder->folder_name }}</div>
                            <div class="folder-count-new">{{ $folder->documents_count }} Files</div>
                        </div>
                    </a>
                </div>
                @empty
                <p class="text-sm text-gray-500 dark:text-gray-400 w-full py-4 text-center">TOS and TOQ folders are being prepared.</p>
                @endforelse
            </div>
        </div>
    @elseif($isTypeLeafFolder)
        {{-- Legacy type leaf: course folders + upload with course picker --}}
        <div class="px-6 py-4">
            <div class="flex items-center justify-between mb-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <i class="fas fa-layer-group mr-1"></i> Select a course folder below, or upload to create one
                </div>
                @if($canUpload)
                <div class="flex gap-2">
                    <button type="button" id="btnFolderUpload" onclick="toggleFolderUpload()" class="btn btn-success doc-action-btn" aria-pressed="false">
                        <i class="fas fa-upload mr-1"></i> Upload to this Folder
                    </button>
                </div>
                @endif
            </div>

            @if($canUpload)
            @include('partials.folder-tree-upload-form')
            @endif

            <div class="folder-container-new flex gap-3 flex-wrap mt-4">
                @forelse($displayFolders as $folder)
                <div class="folder-card-new">
                    <a href="{{ route($docsRoute, ['tab' => $tab, 'folder' => $folder->folder_id]) }}" class="folder-card-link-new">
                        <div class="folder-icon-new" style="background-color: #028a0f; color: white;">
                            <i class="fas fa-folder"></i>
                        </div>
                        <div class="folder-info-new">
                            <div class="folder-name-new">{{ $folder->folder_name }}</div>
                            <div class="folder-count-new">{{ $folder->documents_count }} Files</div>
                        </div>
                    </a>
                </div>
                @empty
                <p class="text-sm text-gray-500 dark:text-gray-400 w-full py-4 text-center">
                    No course folders yet. Upload a file and pick a course — a folder will be created automatically.
                </p>
                @endforelse
            </div>
        </div>
    @elseif($isLeafFolder)
        {{-- LEAF FOLDER: Show upload button + documents --}}
        <div class="px-6 py-4">
            <div class="flex items-center justify-between mb-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <i class="fas fa-folder-open mr-1"></i> {{ $documents->total() }} document(s) in this folder
                </div>
                @if($canUpload)
                <div class="flex gap-2 flex-wrap justify-end">
                    @if(($isCustomSubfolder ?? false) && isset($currentFolder) && (int) $currentFolder->user_id === (int) auth()->id())
                    <button type="button"
                            class="btn btn-sm bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300"
                            onclick="openRenameFolderModal({{ $currentFolder->folder_id }}, @js($currentFolder->folder_name), @js($currentFolder->color ?? '#028a0f'))">
                        <i class="fas fa-pen mr-1"></i> Rename Folder
                    </button>
                    <button type="button"
                            class="btn btn-sm btn-danger"
                            onclick="deleteFolder({{ $currentFolder->folder_id }}, @js($currentFolder->folder_name))">
                        <i class="fas fa-trash mr-1"></i> Delete Folder
                    </button>
                    @endif
                    @if(!($isCustomSubfolder ?? false) && !($isTypeLeafFolder ?? false) && !($useCourseFolderUpload ?? false) && !($isTgUploadLeaf ?? false) && !($isTgSemesterFolder ?? false) && !($isTgSubjectFolder ?? false) && !($isEqUploadLeaf ?? false) && !($isEqSemesterFolder ?? false) && !($isEqSubjectFolder ?? false) && !($isEqAssessmentFolder ?? false))
                    <button type="button" id="btnCreateFolder" onclick="toggleCreateSubfolder()" class="btn btn-primary doc-action-btn" aria-pressed="false">
                        <i class="fas fa-folder-plus mr-1"></i> Create Folder
                    </button>
                    @endif
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


            @if($isTgUploadLeaf ?? false)
                @php
                    $pendingTgGuides = \App\Models\TeachingGuide::query()
                        ->where('folder_id', $currentFolder->folder_id)
                        ->where('status', 'pending')
                        ->when($role === 'faculty', fn ($q) => $q->where('user_id', auth()->id()))
                        ->orderByDesc('created_at')
                        ->get();
                @endphp
                @php
                    $tgViewRoute = match ($role) {
                        'faculty' => 'faculty.teaching-guides.view',
                        'coordinator' => 'coordinator.teaching-guides.view',
                        default => 'dean.teaching-guides.view',
                    };
                    $tgDownloadRoute = match ($role) {
                        'faculty' => 'faculty.teaching-guides.download',
                        'coordinator' => 'coordinator.teaching-guides.download',
                        default => 'dean.teaching-guides.download',
                    };
                @endphp
                @include('partials.pending-dean-approval', [
                    'items' => $pendingTgGuides,
                    'viewRoute' => $tgViewRoute,
                    'downloadRoute' => $tgDownloadRoute,
                ])
            @endif

            @if($isEqUploadLeaf ?? false)
                @php
                    $eqHierarchy = app(\App\Services\AcademicHierarchyService::class);
                    $eqSubjectLabel = $eqHierarchy->subjectLabelFromEqUploadFolder($currentFolder);
                    $eqExamType = $eqHierarchy->examTypeFromEqUploadFolder($currentFolder);
                    $eqSubmissionType = strtoupper(trim((string) $currentFolder->folder_name)) === 'TOS' ? 'tos' : 'toq';
                    $pendingEqQuery = \App\Models\ExamQuestionnaire::query()
                        ->where('status', 'pending')
                        ->where('subject', $eqSubjectLabel)
                        ->where('exam_type', $eqExamType)
                        ->where('submission_type', $eqSubmissionType);
                    if ($role === 'faculty') {
                        $pendingEqQuery->where('submitted_by', auth()->id());
                    }
                    $pendingEqItems = $pendingEqQuery->orderByDesc('created_at')->get();
                @endphp
                @php
                    $eqViewRoute = match ($role) {
                        'faculty' => 'faculty.exam-questionnaires.view',
                        'coordinator' => 'coordinator.exam-questionnaires.view',
                        default => 'dean.exam-questionnaires.view',
                    };
                    $eqDownloadRoute = match ($role) {
                        'faculty' => 'faculty.exam-questionnaires.download',
                        'coordinator' => 'coordinator.exam-questionnaires.download',
                        default => 'dean.exam-questionnaires.download',
                    };
                @endphp
                @include('partials.pending-dean-approval', [
                    'items' => $pendingEqItems,
                    'viewRoute' => $eqViewRoute,
                    'downloadRoute' => $eqDownloadRoute,
                ])
            @endif

            @if($canUpload)
            @include('partials.folder-tree-upload-form')
            @endif
        </div>
    @else
        {{-- FOLDER CARDS --}}
        @if($isCustomFoldersTab && !isset($currentFolder) && $canUpload && $customFoldersCategory)
        <div class="px-6 py-3 flex flex-col gap-3 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <p class="text-sm text-gray-600 dark:text-gray-400 m-0">
                    <i class="fas fa-folder-plus mr-1"></i> Create a folder here, then open it to upload files. Nested folders are not allowed.
                </p>
                <button type="button" id="btnCreateCustomFolder" onclick="toggleCreateCustomFolder()" class="btn btn-primary doc-action-btn text-sm" aria-pressed="false">
                    <i class="fas fa-folder-plus mr-1"></i> Create Folder
                </button>
            </div>
            <form id="createCustomFolderForm" class="hidden" onsubmit="return false;">
                @csrf
                <input type="hidden" name="parent_id" value="{{ $customFoldersCategory->folder_id }}">
                <div class="flex gap-3 items-end flex-wrap">
                    <div class="form-group mb-0 flex-1 min-w-[12rem]">
                        <label class="form-label">Folder Name *</label>
                        <input type="text" name="folder_name" class="form-control" placeholder="Enter folder name" required maxlength="13">
                    </div>
                    <button type="button" onclick="submitCreateCustomFolder()" class="btn btn-primary" id="customFolderSubmitBtn">
                        <i class="fas fa-plus"></i> Create
                    </button>
                    <button type="button" onclick="toggleCreateCustomFolder()" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
        @endif
        <div class="folder-container-new px-6 py-4 flex gap-3 flex-wrap">
            @forelse($displayFolders as $folder)
            @php
                $ownsCustomFolder = $isCustomFoldersTab
                    && !isset($currentFolder)
                    && (int) $folder->user_id === (int) auth()->id();
            @endphp
            <div class="folder-card-new {{ $ownsCustomFolder ? 'folder-card-new--with-actions' : '' }} {{ (isset($folderFilter) && $folderFilter == $folder->folder_id) ? 'folder-card-active' : '' }}">
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
                @if($ownsCustomFolder)
                <div class="folder-actions-new">
                    <button type="button"
                            class="folder-action-btn custom-folder-action-btn"
                            title="Rename folder"
                            aria-label="Rename {{ $folder->folder_name }}"
                            onclick="event.preventDefault(); event.stopPropagation(); openRenameFolderModal({{ $folder->folder_id }}, @js($folder->folder_name), @js($folder->color ?? '#028a0f'))">
                        <i class="fas fa-pen" aria-hidden="true"></i>
                    </button>
                    <button type="button"
                            class="folder-action-btn custom-folder-action-btn custom-folder-action-btn--danger"
                            title="Delete folder"
                            aria-label="Delete {{ $folder->folder_name }}"
                            onclick="event.preventDefault(); event.stopPropagation(); deleteFolder({{ $folder->folder_id }}, @js($folder->folder_name))">
                        <i class="fas fa-trash" aria-hidden="true"></i>
                    </button>
                </div>
                @endif
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

@if($canUpload && (($isLeafFolder ?? false) || ($isTypeLeafFolder ?? false)))
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

    function folderFileExtension(name) {
        const parts = (name || '').toLowerCase().split('.');
        return parts.length > 1 ? parts.pop() : '';
    }

    function folderFileMatchesType(file, docType) {
        const ext = folderFileExtension(file.name);
        if (docType === 'pdf') return ext === 'pdf';
        if (docType === 'word') return ext === 'doc' || ext === 'docx';
        return false;
    }

    function folderUploadSubmitBtn() {
        const form = document.getElementById('folderUploadForm');
        return form ? form.querySelector('button[type="submit"]') : null;
    }

    function resetFolderUploadSubmitBtn() {
        const btn = folderUploadSubmitBtn();
        if (!btn) return;
        btn.disabled = false;
        btn.innerHTML = btn.dataset.originalHtml || '<i class="fas fa-upload"></i> Upload';
    }

    function setFolderFileError(message) {
        const errEl = document.getElementById('folderFileError');
        if (!errEl) return;
        if (message) {
            errEl.textContent = message;
            errEl.classList.remove('hidden');
        } else {
            errEl.textContent = '';
            errEl.classList.add('hidden');
        }
    }

    function folderUploadFail(message) {
        if (typeof showToast === 'function') showToast(message, 'error');
        setFolderFileError(message);
        resetFolderUploadSubmitBtn();
    }

    function applyFolderFileInputFiles(fileInput, fileList) {
        const dt = new DataTransfer();
        Array.from(fileList).slice(0, 3).forEach(f => dt.items.add(f));
        fileInput.files = dt.files;
        fileInput.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function validateFolderSelectedFiles(showMessage) {
        const docType = document.getElementById('folderDocType')?.value;
        const fileInput = document.getElementById('folderFileInput');
        if (!fileInput) return { ok: false, message: 'File input not found.' };

        if (!docType) {
            fileInput.value = '';
            setFolderFileError('');
            return { ok: false, message: 'Please select a Document Type first.' };
        }

        const files = Array.from(fileInput.files || []);
        if (files.length === 0) {
            setFolderFileError('');
            return { ok: false, message: 'Please choose at least one file.' };
        }

        const valid = [];
        const rejected = [];
        files.forEach(f => {
            if (folderFileMatchesType(f, docType)) valid.push(f);
            else rejected.push(f.name);
        });

        if (rejected.length) {
            applyFolderFileInputFiles(fileInput, valid);
            const typeLabel = docType === 'pdf' ? 'PDF (.pdf)' : 'Word (.doc, .docx)';
            const msg = valid.length
                ? 'Removed ' + rejected.join(', ') + '. Only ' + typeLabel + ' files are allowed.'
                : rejected[0] + ' is not allowed. Choose ' + typeLabel + ' only.';
            if (showMessage) {
                setFolderFileError(msg);
                if (typeof showToast === 'function') showToast(msg, 'error');
            }
            return { ok: valid.length > 0, message: msg, files: valid };
        }

        if (valid.length > 3) {
            applyFolderFileInputFiles(fileInput, valid.slice(0, 3));
            const msg = 'Maximum 3 files per upload.';
            if (showMessage) {
                setFolderFileError(msg);
                if (typeof showToast === 'function') showToast(msg, 'error');
            }
            return { ok: true, message: msg, files: valid.slice(0, 3) };
        }

        setFolderFileError('');
        return { ok: true, message: '', files: valid };
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
            setFolderFileError('');
        } else if (type === 'pdf') {
            fileInput.disabled = false;
            fileInput.setAttribute('accept', '.pdf,application/pdf');
            fileHelp.innerHTML = '<i class="fas fa-file-pdf"></i> PDF files only (Max: 10MB)';
            validateFolderSelectedFiles(true);
        } else if (type === 'word') {
            fileInput.disabled = false;
            fileInput.setAttribute('accept', '.doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            fileHelp.innerHTML = '<i class="fas fa-file-word"></i> DOC, DOCX only (Max: 10MB)';
            validateFolderSelectedFiles(true);
        }
        resetFolderUploadSubmitBtn();
    });

    document.getElementById('folderFileInput')?.addEventListener('change', function() {
        validateFolderSelectedFiles(true);
        resetFolderUploadSubmitBtn();
    });

    document.getElementById('folderUploadForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        e.stopPropagation();

        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn && !submitBtn.dataset.originalHtml) {
            submitBtn.dataset.originalHtml = submitBtn.innerHTML;
        }

        const check = validateFolderSelectedFiles(true);
        if (!check.ok) {
            folderUploadFail(check.message || 'Please fix the selected files.');
            return;
        }

        @if($useItSubjectPicker && !$useCourseSelect)
        if (typeof folderSubjectPickerValidate === 'function' && !folderSubjectPickerValidate()) {
            resetFolderUploadSubmitBtn();
            return;
        }
        @endif

        @if($useCourseSelect)
        if (typeof folderCoursePickerValidate === 'function' && !folderCoursePickerValidate()) {
            resetFolderUploadSubmitBtn();
            return;
        }
        @endif

        @if($shareableUploadTab)
        if (typeof folderRecipientPickerValidate === 'function' && !folderRecipientPickerValidate()) {
            resetFolderUploadSubmitBtn();
            return;
        }
        @endif

        const originalText = submitBtn.dataset.originalHtml || submitBtn.innerHTML;
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

</script>
@endpush
@endif

@if($isCustomFoldersTab && $canUpload)
@push('scripts')
<script>
    function toggleCreateCustomFolder() {
        const form = document.getElementById('createCustomFolderForm');
        const btn = document.getElementById('btnCreateCustomFolder');
        if (!form || !btn) return;
        form.classList.toggle('hidden');
        const isOpen = !form.classList.contains('hidden');
        btn.classList.toggle('is-active', isOpen);
        btn.setAttribute('aria-pressed', isOpen ? 'true' : 'false');
    }

    async function submitCreateCustomFolder() {
        const form = document.getElementById('createCustomFolderForm');
        const btn = document.getElementById('customFolderSubmitBtn');
        if (!form || !btn || btn.disabled) return;

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

            if (response.status === 429) {
                showToast('Please wait a few seconds before creating another folder.', 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
                return;
            }

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
</script>
@endpush
@endif

@if($canUpload)
@include('partials.folder-modals')
@endif
