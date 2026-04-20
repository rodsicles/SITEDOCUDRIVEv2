@extends('layouts.dashboard')

@section('title', 'Exam Questionnaires - Coordinator')
@section('page-title', 'Exam Questionnaires')
@section('page-subtitle', 'Review and approve faculty exam questionnaires')

@section('sidebar')
    @include('partials.coordinator-sidebar')
@endsection

@section('content')

    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Exam Questionnaire Submissions</h3>
            <span class="badge badge-info">{{ $questionnaires->total() }} Submissions</span>
        </div>

        <div class="documents-filter flex items-center gap-4 flex-wrap">
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

        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-10"></th>
                    <th>Title</th>
                    <th>Subject</th>
                    <th>Faculty</th>
                    <th>Exam Type</th>
                    <th>Semester</th>
                    <th>Status</th>
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
                    <td><strong>{{ $q->title }}</strong></td>
                    <td><span class="doc-category-badge">{{ $q->subject }}</span></td>
                    <td>{{ $q->submitter->employee->full_name ?? $q->submitter->username }}</td>
                    <td>{{ $q->exam_type }}</td>
                    <td>{{ $q->semester }}</td>
                    <td>
                        @if($q->isPending())
                            <span class="badge" style="background:#b45309;color:#fff;">Pending</span>
                        @elseif($q->isApproved())
                            <span class="badge badge-success">Approved</span>
                        @else
                            <span class="badge badge-danger">Rejected</span>
                            @if($q->remarks)
                                <div class="text-xs text-gray-500 mt-1">{{ $q->remarks }}</div>
                            @endif
                        @endif
                    </td>
                    <td>{{ $q->created_at->format('M d, Y') }}</td>
                    <td>
                        <div class="doc-action-btns">
                            <a href="{{ route('coordinator.exam-questionnaires.view', $q->id) }}" target="_blank" class="btn btn-primary text-xs">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="{{ route('coordinator.exam-questionnaires.download', $q->id) }}" class="btn btn-success text-xs">
                                <i class="fas fa-download"></i> Download
                            </a>
                            @if($q->isPending())
                                <form action="{{ route('coordinator.exam-questionnaires.approve', $q->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-primary text-xs"><i class="fas fa-check"></i> Approve</button>
                                </form>
                                <button type="button" class="btn btn-danger text-xs" onclick="openRejectModal({{ $q->id }})">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-gray-500 dark:text-gray-400 py-8">No submissions found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-5">{{ $questionnaires->links() }}</div>
    </div>

    {{-- Reject Modal --}}
    <div id="rejectModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
        <div class="content-card" style="width:100%;max-width:480px;margin:auto;">
            <div class="card-header">
                <h3 class="card-title">Reject Questionnaire</h3>
                <button onclick="closeRejectModal()" class="btn btn-sm bg-gray-200 dark:bg-gray-700"><i class="fas fa-times"></i></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="p-4">
                    <label class="form-label">Reason for Rejection <span class="text-red-500">*</span></label>
                    <textarea name="remarks" class="form-control" rows="3" maxlength="500" required placeholder="Provide feedback for the faculty..."></textarea>
                </div>
                <div class="px-4 pb-4 flex gap-2">
                    <button type="submit" class="btn btn-danger"><i class="fas fa-times"></i> Reject</button>
                    <button type="button" onclick="closeRejectModal()" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRejectModal(id) {
            document.getElementById('rejectForm').action = '/coordinator/exam-questionnaires/' + id + '/reject';
            var modal = document.getElementById('rejectModal');
            modal.style.display = 'flex';
        }
        function closeRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
        }
    </script>
@endsection
