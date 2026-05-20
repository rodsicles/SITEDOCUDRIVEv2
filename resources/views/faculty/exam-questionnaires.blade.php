@extends('layouts.dashboard')

@section('title', 'Exam Questionnaires - Faculty')
@section('page-title', 'Exam Questionnaires')
@section('page-subtitle', 'View approved questionnaires and track your submissions')

@section('sidebar')
    @include('partials.faculty-sidebar')
@endsection

@section('content')
    @php
        $month = now()->month;
        $year  = now()->year;
        $currentSem = $month >= 8 ? '1st Semester' : '2nd Semester';
        $ayStart = $month >= 8 ? $year : $year - 1;
        $currentAY = "AY {$ayStart}-" . ($ayStart + 1);
        $eqIndexRoute = route('faculty.exam-questionnaires.index');
    @endphp
    <div class="content-card mb-4" style="border-left: 3px solid #028a0f;">
        <div class="p-4 flex items-center gap-3">
            <div class="stat-icon-horizontal"><i class="fas fa-info-circle"></i></div>
            <div>
                <div class="font-semibold text-gray-700 dark:text-gray-200">Current: {{ $currentSem }} {{ $currentAY }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Upload exam questionnaires from Documents &rarr; Exam Questionnaires. Files stay pending until the Dean approves them.</div>
            </div>
        </div>
    </div>

    <div class="content-card">
        <div class="card-header resource-list-header">
            <div class="resource-list-header__main">
                <h3 class="card-title mb-0">Available Exam Questionnaires</h3>
                @include('partials.resource-list-toolbar', [
                    'action' => $eqIndexRoute,
                    'search' => $search ?? '',
                    'sort' => $sort ?? 'date_desc',
                    'searchPlaceholder' => 'Search subject or exam type...',
                ])
            </div>
            <span class="badge badge-info resource-list-header__count">{{ $questionnaires->total() }} Files</span>
        </div>

        <div class="px-4 pb-2">
            @include('partials.pending-dean-approval', [
                'items' => $pendingSubmissions ?? collect(),
                'viewRoute' => 'faculty.exam-questionnaires.view',
                'downloadRoute' => 'faculty.exam-questionnaires.download',
            ])
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-10"></th>
                    <th>Subject</th>
                    <th>Type</th>
                    <th>Exam Type</th>
                    <th>Semester</th>
                    <th>Academic Year</th>
                    <th>Date Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($questionnaires as $q)
                <tr>
                    <td>
                        <div class="w-9 h-9 flex items-center justify-center bg-gray-100 dark:bg-gray-700 text-lg">
                            <i class="fas fa-file-pdf text-red-700"></i>
                        </div>
                    </td>
                    <td>
                        <strong>{{ $q->subject }}</strong>
                        <div class="doc-title-cell mt-1">
                            <button type="button"
                                    class="doc-rename-handle"
                                    title="Rename file"
                                    aria-label="Rename {{ $q->title }}"
                                    onclick="openRenameSubmissionModal({{ $q->id }}, @js($q->title))">
                                <i class="fas fa-pen" aria-hidden="true"></i>
                                <span class="doc-rename-label">Rename</span>
                            </button>
                            <span class="doc-title-text text-sm text-gray-600 dark:text-gray-400" id="eq-title-sub-{{ $q->id }}">{{ $q->title }}</span>
                        </div>
                    </td>
                    <td><span class="doc-category-badge">{{ strtoupper($q->submission_type ?? 'toq') }}</span></td>
                    <td><span class="doc-category-badge">{{ $q->exam_type }}</span></td>
                    <td>{{ $q->semester }}</td>
                    <td>{{ $q->academic_year }}</td>
                    <td>{{ $q->created_at->format('M d, Y') }}</td>
                    <td>
                        <div class="doc-action-btns">
                            <a href="{{ route('faculty.exam-questionnaires.view', $q->id) }}" target="_blank" class="btn btn-action-view text-xs">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="{{ route('faculty.exam-questionnaires.download', $q->id) }}" class="btn btn-action-download text-xs">
                                <i class="fas fa-download"></i> Download
                            </a>
                            <form id="delete-eq-{{ $q->id }}" action="{{ route('faculty.exam-questionnaires.destroy', $q->id) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                            </form>
                            <button type="button" onclick="confirmSubmissionDelete('eq', {{ $q->id }})" class="btn btn-danger text-xs">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-gray-500 dark:text-gray-400 py-8">No approved exam questionnaires available yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-5">{{ $questionnaires->links() }}</div>
    </div>

    @if(isset($rejectedSubmissions) && $rejectedSubmissions->isNotEmpty())
    <div class="content-card mt-5">
        <div class="card-header">
            <h3 class="card-title">Rejected Submissions</h3>
            <span class="badge badge-danger">{{ $rejectedSubmissions->count() }}</span>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Type</th>
                    <th>Exam Type</th>
                    <th>Remarks</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rejectedSubmissions as $q)
                <tr>
                    <td><strong>{{ $q->subject }}</strong></td>
                    <td><span class="doc-category-badge">{{ strtoupper($q->submission_type ?? 'toq') }}</span></td>
                    <td><span class="doc-category-badge">{{ $q->exam_type }}</span></td>
                    <td class="text-xs text-gray-500">{{ $q->remarks ?? '—' }}</td>
                    <td>{{ $q->created_at->format('M d, Y') }}</td>
                    <td>
                        <div class="doc-action-btns">
                            <a href="{{ route('faculty.exam-questionnaires.view', $q->id) }}" target="_blank" class="btn btn-action-view text-xs"><i class="fas fa-eye"></i> View</a>
                            <a href="{{ route('faculty.exam-questionnaires.download', $q->id) }}" class="btn btn-action-download text-xs"><i class="fas fa-download"></i> Download</a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @include('partials.rename-submission-modal', [
        'renameRouteSample' => route('faculty.exam-questionnaires.rename', ['id' => 1]),
        'titleIdPrefix' => 'eq-title-sub-',
    ])
@endsection

@push('scripts')
<script>
function confirmSubmissionDelete(kind, id) {
    const label = kind === 'tg' ? 'teaching guide' : 'exam questionnaire';
    Swal.fire({
        title: 'Delete ' + label + '?',
        text: 'This removes the file from your list. If it was shared in Documents, it will move to Recycle Bin.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        customClass: { popup: 'swal-flat' }
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById('delete-' + kind + '-' + id);
            if (form) form.submit();
        }
    });
}
</script>
@endpush
