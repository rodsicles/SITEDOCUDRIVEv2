@extends('layouts.dashboard')

@section('title', 'Teaching Guides - Dean')
@section('page-title', 'Teaching Guides')
@section('page-subtitle', 'Open new submissions, leave revision notes, and track revisions')

@section('sidebar')
    @if(auth()->user()->isSecretary())
        @include('partials.secretary-sidebar')
    @else
        @include('partials.dean-sidebar')
    @endif
@endsection

@section('content')

    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Teaching Guide Submissions</h3>
            <span class="badge badge-info">{{ $guides->total() }} Submissions</span>
            @if(($pendingCount ?? 0) > 0)
                <span class="badge badge-warning">{{ $pendingCount }} New for you</span>
            @endif
        </div>

        <div class="submission-toolbar">
            <nav class="ui-segmented" aria-label="Review filter">
                <a href="{{ route('dean.teaching-guides.index', array_filter(['semester' => $semesterFilter ?? null, 'search' => $search ?: null])) }}"
                   class="{{ empty($reviewFilter) && ($statusFilter ?? '') !== 'rejected' ? 'is-active' : '' }}">All</a>
                <a href="{{ route('dean.teaching-guides.index', array_filter(['review' => 'new', 'semester' => $semesterFilter ?? null, 'search' => $search ?: null])) }}"
                   class="{{ ($reviewFilter ?? '') === 'new' ? 'is-active' : '' }}">New for you{{ ($pendingCount ?? 0) > 0 ? ' · '.$pendingCount : '' }}</a>
                <a href="{{ route('dean.teaching-guides.index', array_filter(['review' => 'needs_revision', 'semester' => $semesterFilter ?? null, 'search' => $search ?: null])) }}"
                   class="{{ ($reviewFilter ?? '') === 'needs_revision' ? 'is-active' : '' }}">Needs revision</a>
                <a href="{{ route('dean.teaching-guides.index', array_filter(['status' => 'rejected', 'semester' => $semesterFilter ?? null, 'search' => $search ?: null])) }}"
                   class="{{ ($statusFilter ?? '') === 'rejected' ? 'is-active' : '' }}">Rejected (earlier)</a>
            </nav>
            <div class="submission-toolbar__group">
                <label class="submission-toolbar__label">Semester</label>
                <select onchange="window.location.href=this.value" class="form-control submission-toolbar__select">
                    <option value="{{ route('dean.teaching-guides.index', array_filter(['status' => $statusFilter ?? null])) }}">All</option>
                    @foreach(['1st','2nd'] as $sem)
                        <option value="{{ route('dean.teaching-guides.index', array_filter(['status' => $statusFilter ?? null, 'semester' => $sem])) }}" {{ ($semesterFilter ?? '') === $sem ? 'selected' : '' }}>{{ $sem }} Semester</option>
                    @endforeach
                </select>
            </div>
            <form action="{{ route('dean.teaching-guides.index') }}" method="GET" class="submission-toolbar__search">
                @if($statusFilter ?? false)<input type="hidden" name="status" value="{{ $statusFilter }}">@endif
                @if($semesterFilter ?? false)<input type="hidden" name="semester" value="{{ $semesterFilter }}">@endif
                <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Search title, subject, faculty…">
                <button type="submit" class="btn btn-primary text-sm" title="Search" aria-label="Search"><i class="fas fa-search"></i></button>
                @if($search)
                    <a href="{{ route('dean.teaching-guides.index', array_filter(['status' => $statusFilter ?? null, 'semester' => $semesterFilter ?? null])) }}" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm" title="Clear search"><i class="fas fa-times"></i></a>
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
                    <th>Semester</th>
                    <th>Review</th>
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
                    <td><strong>{{ $guide->title }}</strong></td>
                    <td><span class="doc-category-badge">{{ $guide->subject }}</span></td>
                    <td>{{ $guide->uploader ? ($guide->uploader->employee->full_name ?? $guide->uploader->username) : 'Unknown' }}</td>
                    <td><span class="doc-category-badge">TG</span></td>
                    <td>{{ $guide->semester ?? '—' }}</td>
                    <td class="submission-approval-cell">
                        @include('partials.submission-approval-status', ['submission' => $guide])
                    </td>
                    <td>{{ $guide->created_at->format('M d, Y') }}</td>
                    <td class="submission-action-cell text-right">
                        @include('partials.submission-review-actions', [
                            'submission' => $guide,
                            'popoverPrefix' => 'tg',
                            'viewUrl' => route('dean.teaching-guides.view', $guide->id),
                            'downloadUrl' => route('dean.teaching-guides.download', $guide->id),
                        ])
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-gray-500 dark:text-gray-400 py-8">No submissions found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="mt-5">{{ $guides->links() }}</div>
    </div>

    @include('partials.submission-review-table-scripts')

    @include('partials.submission-note-modal')

@endsection
