@extends('layouts.dashboard')

@section('title', 'Teaching Guides')
@section('page-title', 'Teaching Guides')
@section('page-subtitle', 'Upload and manage teaching guides for faculty â€” organized by school year, semester, and subject')

@section('sidebar')
    @include('partials.coordinator-sidebar')
@endsection

@section('content')
    <div class="content-card mb-5">
        <div class="card-header">
            <h3 class="card-title">Upload Teaching Guide</h3>
            <span class="text-xs text-gray-500 dark:text-gray-400">I.T. subjects only â€” folders are created automatically.</span>
        </div>
        <form action="{{ route('coordinator.teaching-guides.store') }}" method="POST" enctype="multipart/form-data" data-request-guard>
            @csrf
            <div class="grid grid-cols-2 gap-4 p-4">
                <div class="form-group">
                    <label class="form-label">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" class="form-control" maxlength="150" required value="{{ old('title') }}">
                    @error('title')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
                @include('partials.academic-hierarchy-fields', ['mode' => 'teaching-guide', 'pickerId' => 'tgSubjectPicker'])
                @include('partials.recipient-picker', ['pickerId' => 'tgRecipientPicker', 'role' => 'dean'])
                <div class="form-group col-span-2">
                    <label class="form-label">Files (PDF or Word, max 10MB each) <span class="text-red-500">*</span></label>
                    <input type="file" name="files[]" class="form-control" accept=".pdf,.doc,.docx" required multiple data-dropzone="1">
                    @error('files')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                    @error('files.*')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="px-4 pb-4">
                <button type="submit" class="btn btn-primary" onclick="return (typeof tgSubjectPickerValidate !== 'function' || tgSubjectPickerValidate()) && (typeof tgRecipientPickerValidate !== 'function' || tgRecipientPickerValidate());">
                    <i class="fas fa-upload"></i> Upload & Notify Recipients
                </button>
            </div>
        </form>
    </div>

    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">All Teaching Guides</h3>
            <span class="badge badge-info">{{ $guides->total() }} Files</span>
        </div>

        <div class="documents-filter flex items-center gap-4 flex-wrap p-4">
            <form action="{{ route('coordinator.teaching-guides.index') }}" method="GET" class="flex items-center gap-3 flex-wrap w-full">
                @include('partials.school-year-filter', ['selected' => $academicYearStart ?? ''])
                <select name="semester" class="form-control text-sm" style="min-width:140px">
                    <option value="">All Semesters</option>
                    <option value="1st" {{ ($semesterFilter ?? '') === '1st' ? 'selected' : '' }}>1st Semester</option>
                    <option value="2nd" {{ ($semesterFilter ?? '') === '2nd' ? 'selected' : '' }}>2nd Semester</option>
                </select>
                <input type="text" name="search" value="{{ $search }}" class="form-control text-sm" placeholder="Search title or subject..." style="min-width:220px">
                <button type="submit" class="btn btn-primary text-sm"><i class="fas fa-search"></i> Filter</button>
                @if($search || $semesterFilter || ($academicYearStart ?? ''))
                    <a href="{{ route('coordinator.teaching-guides.index') }}" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm"><i class="fas fa-times"></i> Clear</a>
                @endif
            </form>
            @if(!empty($archiveYears))
            <p class="text-xs text-gray-500 w-full mb-0">
                <i class="fas fa-archive mr-1"></i> Archive history: {{ collect($archiveYears)->map(fn($y) => 'AY '.$y.'-'.($y+1))->implode(', ') }}
            </p>
            @endif
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-10"></th>
                    <th>Title</th>
                    <th>Subject</th>
                    <th>School Year</th>
                    <th>Semester</th>
                    <th>Folder</th>
                    <th>Uploaded By</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($guides as $guide)
                <tr>
                    <td>
                        <div class="w-9 h-9 flex items-center justify-center bg-gray-100 dark:bg-gray-700 text-lg">
                            @if($guide->file_type === 'pdf')
                                <i class="fas fa-file-pdf text-red-700"></i>
                            @else
                                <i class="fas fa-file-word text-blue-700"></i>
                            @endif
                        </div>
                    </td>
                    <td><strong>{{ $guide->title }}</strong></td>
                    <td><span class="doc-category-badge">{{ $guide->subject }}</span></td>
                    <td class="text-xs">{{ $guide->academic_year ? 'AY '.$guide->academic_year : 'â€”' }}</td>
                    <td class="text-xs">{{ $guide->semester ?? 'â€”' }}</td>
                    <td class="text-xs text-gray-600 dark:text-gray-400">{{ $guide->folder?->folder_name ?? 'â€”' }}</td>
                    <td>{{ $guide->uploader->employee->full_name ?? $guide->uploader->username }}</td>
                    <td>{{ $guide->created_at->format('M d, Y') }}</td>
                    <td>
                        <div class="doc-action-btns">
                            <a href="{{ route('coordinator.teaching-guides.download', $guide->id) }}" class="btn btn-action-download text-xs">
                                <i class="fas fa-download"></i> Download
                            </a>
                            <form action="{{ route('coordinator.teaching-guides.destroy', $guide->id) }}" method="POST" onsubmit="return confirm('Delete this guide?')" data-request-guard>
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger text-xs"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-gray-500 dark:text-gray-400 py-8">No teaching guides uploaded yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-5">{{ $guides->links() }}</div>
    </div>
@endsection

