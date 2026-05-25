@extends('layouts.dashboard')

@section('title', 'Pending Teaching Guides - Coordinator')
@section('page-title', 'Pending Teaching Guides')
@section('page-subtitle', 'Review and approve teaching guide submissions from your department faculty')

@section('sidebar')
    @include('partials.coordinator-sidebar')
@endsection

@section('content')
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Teaching Guide Submissions</h3>
            <span class="badge badge-info">{{ $guides->total() }} Submissions</span>
            @if(($pendingCount ?? 0) > 0)
                <span class="badge" style="background:#b45309;color:#fff;">{{ $pendingCount }} Pending Review</span>
            @endif
        </div>

        <p class="px-4 pt-2 pb-0 text-xs text-gray-500 dark:text-gray-400 mb-0">
            <i class="fas fa-info-circle mr-1"></i>
            You only see submissions from faculty in your department. Upload new files from <strong>Documents → Teaching Guides</strong>.
        </p>

        <div class="px-4 pb-4 flex items-center gap-4 flex-wrap pt-3">
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium whitespace-nowrap text-gray-700 dark:text-gray-300">Status:</label>
                <select onchange="window.location.href=this.value" class="form-control text-sm">
                    <option value="{{ route('coordinator.teaching-guides.index') }}">All</option>
                    @foreach(['pending','approved','rejected'] as $s)
                        <option value="{{ route('coordinator.teaching-guides.index', ['status' => $s]) }}" {{ ($statusFilter ?? '') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium whitespace-nowrap text-gray-700 dark:text-gray-300">Semester:</label>
                <select onchange="window.location.href=this.value" class="form-control text-sm">
                    <option value="{{ route('coordinator.teaching-guides.index', array_filter(['status' => $statusFilter ?? null])) }}">All</option>
                    @foreach(['1st','2nd'] as $sem)
                        <option value="{{ route('coordinator.teaching-guides.index', array_filter(['status' => $statusFilter ?? null, 'semester' => $sem])) }}" {{ ($semesterFilter ?? '') === $sem ? 'selected' : '' }}>{{ $sem }} Semester</option>
                    @endforeach
                </select>
            </div>
            <form action="{{ route('coordinator.teaching-guides.index') }}" method="GET" class="flex items-center gap-2 ml-auto flex-wrap">
                @if($statusFilter ?? false)<input type="hidden" name="status" value="{{ $statusFilter }}">@endif
                @if($semesterFilter ?? false)<input type="hidden" name="semester" value="{{ $semesterFilter }}">@endif
                @include('partials.school-year-filter', ['selected' => $academicYearStart ?? ''])
                <input type="text" name="search" value="{{ $search }}" class="form-control text-sm" placeholder="Search title, subject, or faculty..." style="min-width:220px">
                <button type="submit" class="btn btn-primary text-sm"><i class="fas fa-search"></i></button>
                @if($search || $semesterFilter || ($statusFilter ?? false) || ($academicYearStart ?? ''))
                    <a href="{{ route('coordinator.teaching-guides.index') }}" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm"><i class="fas fa-times"></i></a>
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
                    <td class="submission-approval-cell">
                        @include('partials.submission-approval-status', ['submission' => $guide])
                    </td>
                    <td>{{ $guide->created_at->format('M d, Y') }}</td>
                    <td class="submission-action-cell text-right">
                        @if($canReviewSubmissions ?? false)
                            @include('partials.submission-review-actions', [
                                'submission' => $guide,
                                'popoverPrefix' => 'tg',
                                'viewUrl' => route('coordinator.teaching-guides.view', $guide->id),
                                'downloadUrl' => route('coordinator.teaching-guides.download', $guide->id),
                                'approveUrl' => route('coordinator.teaching-guides.approve', $guide->id),
                                'rejectOnClick' => 'openTgRejectModal',
                            ])
                        @else
                            @include('partials.submission-browse-actions', [
                                'viewUrl' => route('coordinator.teaching-guides.view', $guide->id),
                                'downloadUrl' => route('coordinator.teaching-guides.download', $guide->id),
                                'viewLabel' => 'View ' . $guide->title,
                                'downloadLabel' => 'Download ' . $guide->title,
                            ])
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-gray-500 dark:text-gray-400 py-8">No teaching guides found for your department.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="mt-5 px-4 pb-4">{{ $guides->links() }}</div>
    </div>

    @if($canReviewSubmissions ?? false)
    @include('partials.submission-review-table-scripts')

    <div id="tgRejectModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
        <div class="content-card" style="width:100%;max-width:480px;margin:auto;">
            <div class="card-header">
                <h3 class="card-title">Reject Teaching Guide</h3>
                <button type="button" onclick="closeTgRejectModal()" class="btn btn-sm bg-gray-200 dark:bg-gray-700"><i class="fas fa-times"></i></button>
            </div>
            <form id="tgRejectForm" method="POST">
                @csrf
                <div class="p-4">
                    <label class="form-label">Reason for Rejection <span class="text-red-500">*</span></label>
                    <textarea name="remarks" class="form-control" rows="3" maxlength="500" required placeholder="Provide feedback for the faculty..."></textarea>
                </div>
                <div class="px-4 pb-4 flex gap-2">
                    <button type="submit" class="btn btn-danger"><i class="fas fa-times"></i> Reject</button>
                    <button type="button" onclick="closeTgRejectModal()" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openTgRejectModal(id) {
            document.getElementById('tgRejectForm').action = '{{ url('/coordinator/teaching-guides') }}/' + id + '/reject';
            document.getElementById('tgRejectModal').style.display = 'flex';
        }
        function closeTgRejectModal() {
            document.getElementById('tgRejectModal').style.display = 'none';
        }
    </script>
    @endif
@endsection
