@extends('layouts.dashboard')

@section('title', 'Exam Questionnaires - Coordinator')
@section('page-title', 'Exam Questionnaires')
@section('page-subtitle', 'Open new submissions, leave revision notes, and track revisions')

@section('sidebar')
    @include('partials.coordinator-sidebar')
@endsection

@section('content')
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Exam Questionnaire Submissions</h3>
            <span class="badge badge-info">{{ $questionnaires->total() }} Submissions</span>
            @if(($pendingCount ?? 0) > 0)
                <span class="badge" style="background:#b45309;color:#fff;">{{ $pendingCount }} New for you</span>
            @endif
        </div>

        <p class="px-4 pt-2 pb-0 text-xs text-gray-500 dark:text-gray-400 mb-0">
            <i class="fas fa-info-circle mr-1"></i>
            You only see submissions from faculty in your program.
        </p>

        <div class="submission-toolbar">
            <nav class="ui-segmented" aria-label="Review filter">
                <a href="{{ route('coordinator.exam-questionnaires.index', array_filter(['exam_type' => $examTypeFilter ?? null, 'search' => $search ?: null])) }}"
                   class="{{ empty($reviewFilter) && ($statusFilter ?? '') !== 'rejected' ? 'is-active' : '' }}">All</a>
                <a href="{{ route('coordinator.exam-questionnaires.index', array_filter(['review' => 'new', 'exam_type' => $examTypeFilter ?? null, 'search' => $search ?: null])) }}"
                   class="{{ ($reviewFilter ?? '') === 'new' ? 'is-active' : '' }}">New for you{{ ($pendingCount ?? 0) > 0 ? ' · '.$pendingCount : '' }}</a>
                <a href="{{ route('coordinator.exam-questionnaires.index', array_filter(['review' => 'needs_revision', 'exam_type' => $examTypeFilter ?? null, 'search' => $search ?: null])) }}"
                   class="{{ ($reviewFilter ?? '') === 'needs_revision' ? 'is-active' : '' }}">Needs revision</a>
                <a href="{{ route('coordinator.exam-questionnaires.index', array_filter(['status' => 'rejected', 'exam_type' => $examTypeFilter ?? null, 'search' => $search ?: null])) }}"
                   class="{{ ($statusFilter ?? '') === 'rejected' ? 'is-active' : '' }}">Rejected (earlier)</a>
            </nav>
            <div class="submission-toolbar__group">
                <label class="submission-toolbar__label">Exam Type</label>
                <select onchange="window.location.href=this.value" class="form-control submission-toolbar__select">
                    <option value="{{ route('coordinator.exam-questionnaires.index', array_filter(['status' => $statusFilter ?: null])) }}">All</option>
                    @foreach(['Quiz','Prelim','Midterm','Pre-Final','Final'] as $type)
                        <option value="{{ route('coordinator.exam-questionnaires.index', array_filter(['status' => $statusFilter ?: null, 'exam_type' => $type])) }}" {{ $examTypeFilter === $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <form action="{{ route('coordinator.exam-questionnaires.index') }}" method="GET" class="submission-toolbar__search">
                @if($statusFilter)<input type="hidden" name="status" value="{{ $statusFilter }}">@endif
                @if($examTypeFilter)<input type="hidden" name="exam_type" value="{{ $examTypeFilter }}">@endif
                <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Search title, subject, faculty…">
                <button type="submit" class="btn btn-primary text-sm" title="Search" aria-label="Search"><i class="fas fa-search"></i></button>
                @if($search)
                    <a href="{{ route('coordinator.exam-questionnaires.index', array_filter(['status' => $statusFilter ?: null, 'exam_type' => $examTypeFilter ?: null])) }}" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm" title="Clear search"><i class="fas fa-times"></i></a>
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
                    <th>Review</th>
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
                        @if($canReviewSubmissions ?? false)
                            @include('partials.submission-review-actions', [
                                'submission' => $q,
                                'popoverPrefix' => 'eq',
                                'viewUrl' => route('coordinator.exam-questionnaires.view', $q->id),
                                'downloadUrl' => route('coordinator.exam-questionnaires.download', $q->id),
                            ])
                        @else
                            @include('partials.submission-browse-actions', [
                                'viewUrl' => route('coordinator.exam-questionnaires.view', $q->id),
                                'downloadUrl' => route('coordinator.exam-questionnaires.download', $q->id),
                                'viewLabel' => 'View ' . $q->title,
                                'downloadLabel' => 'Download ' . $q->title,
                            ])
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center text-gray-500 dark:text-gray-400 py-8">No submissions found for your program.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="mt-5 px-4 pb-4">{{ $questionnaires->links() }}</div>
    </div>

    @if($canReviewSubmissions ?? false)
    @include('partials.submission-review-table-scripts')

    @include('partials.submission-note-modal')

    @endif
@endsection
