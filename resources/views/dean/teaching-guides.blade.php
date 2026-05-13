@extends('layouts.dashboard')

@section('title', 'Teaching Guides')
@section('page-title', 'Teaching Guides')
@section('page-subtitle', 'Upload and manage teaching guides for faculty')

@section('sidebar')
    @if(auth()->user()->isSecretary())
        @include('partials.secretary-sidebar')
    @else
        @include('partials.dean-sidebar')
    @endif
@endsection

@section('content')
    {{-- Upload Form --}}
    <div class="content-card mb-5">
        <div class="card-header">
            <h3 class="card-title">Upload Teaching Guide</h3>
            <span class="text-xs text-gray-500 dark:text-gray-400">Faculty will be notified automatically after upload.</span>
        </div>
        <form action="{{ route('dean.teaching-guides.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-2 gap-4 p-4">
                <div class="form-group">
                    <label class="form-label">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" class="form-control" maxlength="150" required value="{{ old('title') }}">
                    @error('title')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Subject <span class="text-red-500">*</span></label>
                    <input type="text" name="subject" class="form-control" maxlength="100" required value="{{ old('subject') }}">
                    @error('subject')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
                <div class="form-group col-span-2">
                    <label class="form-label">Upload to Folder <span class="text-red-500">*</span></label>
                    <select name="folder_id" class="form-control" required>
                        <option value="">-- Select Folder --</option>
                        @if($tgRoot)
                            @foreach($tgRoot->children as $semester)
                                <optgroup label="{{ $semester->folder_name }}">
                                    <option value="{{ $semester->folder_id }}">
                                        {{ $semester->folder_name }} (whole semester)
                                    </option>
                                    @foreach($semester->children as $examFolder)
                                        <option value="{{ $examFolder->folder_id }}" {{ old('folder_id') == $examFolder->folder_id ? 'selected' : '' }}>
                                            &nbsp;&nbsp;&nbsp;└ {{ $examFolder->folder_name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        @endif
                    </select>
                    @error('folder_id')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
                <div class="form-group col-span-2">
                    <label class="form-label">Files (PDF or Word, max 10MB each) <span class="text-red-500">*</span></label>
                    <input type="file" name="files[]" class="form-control" accept=".pdf,.doc,.docx" required multiple data-dropzone="1">
                    @error('files')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                    @error('files.*')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="px-4 pb-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Upload & Notify Faculty
                </button>
            </div>
        </form>
    </div>

    {{-- All Teaching Guides --}}
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">All Teaching Guides</h3>
            <span class="badge badge-info">{{ $guides->total() }} Files</span>
        </div>

        <div class="documents-filter flex items-center gap-4 flex-wrap">
            <form action="{{ route('dean.teaching-guides.index') }}" method="GET" class="flex items-center gap-2 ml-auto">
                <input type="text" name="search" value="{{ $search }}" class="form-control text-sm" placeholder="Search title or subject..." style="min-width:220px">
                <button type="submit" class="btn btn-primary text-sm"><i class="fas fa-search"></i></button>
                @if($search)
                    <a href="{{ route('dean.teaching-guides.index') }}" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm"><i class="fas fa-times"></i></a>
                @endif
            </form>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-10"></th>
                    <th>Title</th>
                    <th>Subject</th>
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
                    <td class="text-xs text-gray-600 dark:text-gray-400">{{ $guide->folder?->folder_name ?? '—' }}</td>
                    <td>{{ $guide->uploader->employee->full_name ?? $guide->uploader->username }}</td>
                    <td>{{ $guide->created_at->format('M d, Y') }}</td>
                    <td>
                        <div class="doc-action-btns">
                            <a href="{{ route('dean.teaching-guides.download', $guide->id) }}" class="btn btn-success text-xs">
                                <i class="fas fa-download"></i> Download
                            </a>
                            <form action="{{ route('dean.teaching-guides.destroy', $guide->id) }}" method="POST" onsubmit="return confirm('Delete this guide?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger text-xs"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-gray-500 dark:text-gray-400 py-8">No teaching guides uploaded yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-5">{{ $guides->links() }}</div>
    </div>
@endsection
