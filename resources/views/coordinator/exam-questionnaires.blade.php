@extends('layouts.dashboard')

@section('title', 'Exam Questionnaires - Coordinator')
@section('page-title', 'Exam Questionnaires')
@section('page-subtitle', 'View department exam questionnaire submissions — Dean approves all uploads')

@section('sidebar')
    @include('partials.coordinator-sidebar')
@endsection

@section('content')
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Exam Questionnaire Submissions</h3>
            <span class="badge badge-info">{{ $questionnaires->total() }} Submissions</span>
        </div>

        <div class="px-4 pb-4 flex items-center gap-4 flex-wrap">
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium whitespace-nowrap text-gray-700 dark:text-gray-300">Status:</label>
                <select onchange="window.location.href=this.value" class="form-control text-sm">
                    <option value="{{ route('coordinator.exam-questionnaires.index') }}">All</option>
                    @foreach(['pending','approved','rejected'] as $s)
                        <option value="{{ route('coordinator.exam-questionnaires.index', ['status' => $s]) }}" {{ $statusFilter === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium whitespace-nowrap text-gray-700 dark:text-gray-300">Exam Type:</label>
                <select onchange="window.location.href=this.value" class="form-control text-sm">
                    <option value="{{ route('coordinator.exam-questionnaires.index') }}">All</option>
                    @foreach(['Quiz','Prelim','Midterm','Pre-Final','Final'] as $type)
                        <option value="{{ route('coordinator.exam-questionnaires.index', ['exam_type' => $type]) }}" {{ $examTypeFilter === $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <form action="{{ route('coordinator.exam-questionnaires.index') }}" method="GET" class="flex items-center gap-2 ml-auto">
                @if($statusFilter)<input type="hidden" name="status" value="{{ $statusFilter }}">@endif
                @if($examTypeFilter)<input type="hidden" name="exam_type" value="{{ $examTypeFilter }}">@endif
                <input type="text" name="search" value="{{ $search }}" class="form-control text-sm" placeholder="Search title, subject, or faculty..." style="min-width:220px">
                <button type="submit" class="btn btn-primary text-sm"><i class="fas fa-search"></i></button>
                @if($search)
                    <a href="{{ route('coordinator.exam-questionnaires.index') }}" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm"><i class="fas fa-times"></i></a>
                @endif
            </form>
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
                    <th>Exam Type</th>
                    <th>Semester</th>
                    <th>Approval</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($questionnaires as $q)
                <tr>
                    <td>
                        <div class="w-9 h-9 flex items-center justify-center bg-gray-100 dark:bg-gray-700 text-lg">
                            @if($q->file_type === 'pdf')
                                <i class="fas fa-file-pdf text-red-700"></i>
                            @else
                                <i class="fas fa-file-word text-blue-700"></i>
                            @endif
                        </div>
                    </td>
                    <td><strong class="doc-title-text">{{ $q->title }}</strong></td>
                    <td><span class="doc-category-badge">{{ $q->subject }}</span></td>
                    <td>{{ $q->submitter->employee->full_name ?? $q->submitter->username }}</td>
                    <td><span class="doc-category-badge">{{ strtoupper($q->submission_type ?? 'toq') }}</span></td>
                    <td>{{ $q->exam_type }}</td>
                    <td>{{ $q->semester ?? '—' }}</td>
                    <td class="submission-approval-cell">
                        @include('partials.submission-approval-status', ['submission' => $q])
                    </td>
                    <td>{{ $q->created_at->format('M d, Y') }}</td>
                    <td class="submission-action-cell text-right">
                        @include('partials.submission-browse-actions', [
                            'viewUrl' => route('coordinator.exam-questionnaires.view', $q->id),
                            'downloadUrl' => route('coordinator.exam-questionnaires.download', $q->id),
                            'viewLabel' => 'View ' . $q->title,
                            'downloadLabel' => 'Download ' . $q->title,
                        ])
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center text-gray-500 dark:text-gray-400 py-8">No submissions found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="mt-5 px-4 pb-4">{{ $questionnaires->links() }}</div>
    </div>
@endsection
