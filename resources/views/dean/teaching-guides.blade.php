@extends('layouts.dashboard')

@section('title', 'Pending Teaching Guides')
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
            <span class="badge badge-info">{{ $guides->total() }} Files</span>
        </div>

        <div class="documents-filter flex items-center gap-4 flex-wrap p-4">
            <form action="{{ route('dean.teaching-guides.index') }}" method="GET" class="flex items-center gap-3 flex-wrap w-full">
                @include('partials.school-year-filter', ['selected' => $academicYearStart ?? ''])
                <select name="semester" class="form-control text-sm" style="min-width:140px">
                    <option value="">All Semesters</option>
                    <option value="1st" {{ ($semesterFilter ?? '') === '1st' ? 'selected' : '' }}>1st Semester</option>
                    <option value="2nd" {{ ($semesterFilter ?? '') === '2nd' ? 'selected' : '' }}>2nd Semester</option>
                </select>
                <input type="text" name="search" value="{{ $search }}" class="form-control text-sm" placeholder="Search title or subject..." style="min-width:220px">
                <button type="submit" class="btn btn-primary text-sm"><i class="fas fa-search"></i> Filter</button>
                @if($search || $semesterFilter || ($academicYearStart ?? ''))
                    <a href="{{ route('dean.teaching-guides.index') }}" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm"><i class="fas fa-times"></i> Clear</a>
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
                    <th>Semester</th>
                    <th>Folder</th>
                    <th>Uploaded By</th>
                    <th>Approval</th>
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
                    <td class="text-xs">{{ $guide->semester ?? 'â€”' }}</td>
                    <td class="text-xs text-gray-600 dark:text-gray-400">{{ $guide->folder?->folder_name ?? 'â€”' }}</td>
                    <td>{{ $guide->uploader->employee->full_name ?? $guide->uploader->username }}</td>
                    <td>
                        @if($guide->isPending())
                            <span class="badge" style="background:#b45309;color:#fff;"><i class="fas fa-clock"></i> Not Approved</span>
                        @elseif($guide->isApproved())
                            <span class="badge badge-success"><i class="fas fa-check"></i> Approved</span>
                        @else
                            <span class="badge badge-danger"><i class="fas fa-times"></i> Rejected</span>
                            @if($guide->remarks)
                                <div class="text-xs text-gray-500 mt-1">{{ $guide->remarks }}</div>
                            @endif
                        @endif
                    </td>
                    <td>{{ $guide->created_at->format('M d, Y') }}</td>
                    <td>
                        <div class="doc-action-btns">
                            <a href="{{ route('dean.teaching-guides.download', $guide->id) }}" class="btn btn-action-download text-xs">
                                <i class="fas fa-download"></i> Download
                            </a>
                            @if($guide->isPending())
                                <form action="{{ route('dean.teaching-guides.approve', $guide->id) }}" method="POST" data-request-guard>
                                    @csrf
                                    <button type="submit" class="btn btn-primary text-xs"><i class="fas fa-check"></i> Approve</button>
                                </form>
                                <button type="button" class="btn btn-danger text-xs" onclick="openTgRejectModal({{ $guide->id }})">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            @endif
                            <form action="{{ route('dean.teaching-guides.destroy', $guide->id) }}" method="POST" onsubmit="return confirm('Delete this guide?')" data-request-guard>
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

    {{-- Reject Modal --}}
    <div id="tgRejectModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
        <div class="content-card" style="width:100%;max-width:480px;margin:auto;">
            <div class="card-header">
                <h3 class="card-title">Reject Teaching Guide</h3>
                <button onclick="closeTgRejectModal()" class="btn btn-sm bg-gray-200 dark:bg-gray-700"><i class="fas fa-times"></i></button>
            </div>
            <form id="tgRejectForm" method="POST">
                @csrf
                <div class="p-4">
                    <label class="form-label">Reason for Rejection <span class="text-red-500">*</span></label>
                    <textarea name="remarks" class="form-control" rows="3" maxlength="500" required placeholder="Provide feedback..."></textarea>
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
            document.getElementById('tgRejectForm').action = '{{ url("/dean/teaching-guides") }}/' + id + '/reject';
            document.getElementById('tgRejectModal').style.display = 'flex';
        }
        function closeTgRejectModal() {
            document.getElementById('tgRejectModal').style.display = 'none';
        }
    </script>
@endsection
