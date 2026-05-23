@extends('layouts.dashboard')

@section('title', 'Pending Teaching Guides - Dean')
@section('page-title', 'Pending Teaching Guides')
@section('page-subtitle', 'Review and approve faculty teaching guide submissions')

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
                <span class="badge" style="background:#b45309;color:#fff;">{{ $pendingCount }} Pending Review</span>
            @endif
        </div>

        <div class="px-4 pb-4 flex items-center gap-4 flex-wrap">
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium whitespace-nowrap text-gray-700 dark:text-gray-300">Status:</label>
                <select onchange="window.location.href=this.value" class="form-control text-sm">
                    <option value="{{ route('dean.teaching-guides.index') }}">All</option>
                    @foreach(['pending','approved','rejected'] as $s)
                        <option value="{{ route('dean.teaching-guides.index', ['status' => $s]) }}" {{ ($statusFilter ?? '') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium whitespace-nowrap text-gray-700 dark:text-gray-300">Semester:</label>
                <select onchange="window.location.href=this.value" class="form-control text-sm">
                    <option value="{{ route('dean.teaching-guides.index', array_filter(['status' => $statusFilter ?? null])) }}">All</option>
                    @foreach(['1st','2nd'] as $sem)
                        <option value="{{ route('dean.teaching-guides.index', array_filter(['status' => $statusFilter ?? null, 'semester' => $sem])) }}" {{ ($semesterFilter ?? '') === $sem ? 'selected' : '' }}>{{ $sem }} Semester</option>
                    @endforeach
                </select>
            </div>
            <form action="{{ route('dean.teaching-guides.index') }}" method="GET" class="flex items-center gap-2 ml-auto">
                @if($statusFilter ?? false)<input type="hidden" name="status" value="{{ $statusFilter }}">@endif
                @if($semesterFilter ?? false)<input type="hidden" name="semester" value="{{ $semesterFilter }}">@endif
                <input type="text" name="search" value="{{ $search }}" class="form-control text-sm" placeholder="Search title, subject, or faculty..." style="min-width:220px">
                <button type="submit" class="btn btn-primary text-sm"><i class="fas fa-search"></i></button>
                @if($search)
                    <a href="{{ route('dean.teaching-guides.index') }}" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm"><i class="fas fa-times"></i></a>
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
                    <td><strong>{{ $guide->title }}</strong></td>
                    <td><span class="doc-category-badge">{{ $guide->subject }}</span></td>
                    <td>{{ $guide->uploader->employee->full_name ?? $guide->uploader->username }}</td>
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
                            'approveUrl' => route('dean.teaching-guides.approve', $guide->id),
                            'rejectOnClick' => 'openTgRejectModal',
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

    {{-- Reject Modal --}}
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
            document.getElementById('tgRejectForm').action = '{{ url('/dean/teaching-guides') }}/' + id + '/reject';
            document.getElementById('tgRejectModal').style.display = 'flex';
        }
        function closeTgRejectModal() {
            document.getElementById('tgRejectModal').style.display = 'none';
        }
    </script>
@endsection
