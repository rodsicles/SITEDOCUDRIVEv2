@extends('layouts.dashboard')

@section('title', 'Teaching Guides')
@section('page-title', 'Teaching Guides')
@section('page-subtitle', 'Browse department teaching guides by school year, semester, and subject')

@section('sidebar')
    @include('partials.coordinator-sidebar')
@endsection

@section('content')
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Teaching Guides</h3>
            <span class="badge badge-info">{{ $guides->total() }} Files</span>
        </div>

        <p class="px-4 pt-2 pb-0 text-xs text-gray-500 dark:text-gray-400 mb-0">
            <i class="fas fa-info-circle mr-1"></i>
            Upload new files from <strong>Documents → Teaching Guides</strong> using the school year, semester, and subject folders.
        </p>

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

        <div class="submission-review-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-10"></th>
                    <th>Title</th>
                    <th>Subject</th>
                    <th>Faculty</th>
                    <th>Type</th>
                    <th>Semester</th>
                    <th>School Year</th>
                    <th>Approval</th>
                    <th>Submitted</th>
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
                    <td><strong class="doc-title-text">{{ $guide->title }}</strong></td>
                    <td><span class="doc-category-badge">{{ $guide->subject }}</span></td>
                    <td>{{ $guide->uploader->employee->full_name ?? $guide->uploader->username }}</td>
                    <td><span class="doc-category-badge">TG</span></td>
                    <td>{{ $guide->semester ?? '—' }}</td>
                    <td class="text-sm text-gray-600 dark:text-gray-400">{{ $guide->academic_year ? 'AY '.$guide->academic_year : '—' }}</td>
                    <td class="submission-approval-cell">
                        @include('partials.submission-approval-status', ['submission' => $guide])
                    </td>
                    <td>{{ $guide->created_at->format('M d, Y') }}</td>
                    <td class="submission-action-cell text-right">
                        @include('partials.submission-browse-actions', [
                            'viewUrl' => route('coordinator.teaching-guides.view', $guide->id),
                            'downloadUrl' => route('coordinator.teaching-guides.download', $guide->id),
                            'viewLabel' => 'View ' . $guide->title,
                            'downloadLabel' => 'Download ' . $guide->title,
                            'deleteUrl' => route('coordinator.teaching-guides.destroy', $guide->id),
                            'deleteConfirm' => 'Delete this teaching guide?',
                        ])
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center text-gray-500 dark:text-gray-400 py-8">No teaching guides found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="mt-5 px-4 pb-4">{{ $guides->links() }}</div>
    </div>
@endsection
