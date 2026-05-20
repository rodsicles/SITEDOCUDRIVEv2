@extends('layouts.dashboard')

@section('title', 'Teaching Guides - Faculty')
@section('page-title', 'Teaching Guides')
@section('page-subtitle', 'View approved guides and track your submissions')

@section('sidebar')
    @include('partials.faculty-sidebar')
@endsection

@section('content')
    @php
        use App\Support\AcademicYear;
        use App\Support\SubmissionLocation;
        $currentAY = AcademicYear::label(AcademicYear::currentStartYear());
        $tgIndexRoute = route('faculty.teaching-guides.index');
    @endphp

    <div class="content-card mb-4" style="border-left: 3px solid #028a0f;">
        <div class="p-4 flex items-center gap-3">
            <div class="stat-icon-horizontal"><i class="fas fa-info-circle"></i></div>
            <div>
                <div class="font-semibold text-gray-700 dark:text-gray-200">Current: {{ $currentAY }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Upload teaching guides from Documents &rarr; Teaching Guides. Files stay pending until the Dean approves them.</div>
            </div>
        </div>
    </div>

    @if(isset($pendingGuides) && $pendingGuides->isNotEmpty())
        @include('partials.pending-dean-approval', [
            'items' => $pendingGuides,
            'viewRoute' => 'faculty.teaching-guides.view',
            'downloadRoute' => 'faculty.teaching-guides.download',
            'showRename' => true,
            'titleIdPrefix' => 'pending-tg-title-',
        ])
    @endif

    <div class="content-card">
        <div class="card-header resource-list-header">
            <div class="resource-list-header__main">
                <h3 class="card-title mb-0">Available Teaching Guides</h3>
                @include('partials.resource-list-toolbar', [
                    'action' => $tgIndexRoute,
                    'search' => $search ?? '',
                    'sort' => $sort ?? 'date_desc',
                    'searchPlaceholder' => 'Search title or subject...',
                ])
            </div>
            <span class="badge badge-info resource-list-header__count">{{ $guides->total() }} Files</span>
        </div>

        <div class="resource-list-table-wrap px-4 pb-4">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="w-10"></th>
                        <th>Title</th>
                        <th>Subject</th>
                        <th>Date Uploaded</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($guides as $guide)
                    @php
                        $folder = $guide->folder ?? $guide->document?->folder;
                        $path = $folder ? SubmissionLocation::folderBreadcrumb($folder) : '';
                        $docsUrl = $folder ? SubmissionLocation::documentsUrl(auth()->user(), $folder) : null;
                    @endphp
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
                        <td class="min-w-[220px]">
                            <a href="{{ route('faculty.teaching-guides.view', $guide->id) }}"
                               class="font-semibold text-[#028a0f] dark:text-[#02b815] hover:underline">
                                {{ $guide->title }}
                            </a>
                            @if($path)
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 block">
                                <i class="fas fa-folder-open mr-1"></i>
                                @if($docsUrl)
                                <a href="{{ $docsUrl }}" class="hover:underline">{{ $path }}</a>
                                @else
                                {{ $path }}
                                @endif
                            </div>
                            @endif
                        </td>
                        <td><span class="doc-category-badge">{{ $guide->subject }}</span></td>
                        <td>{{ $guide->created_at->format('M d, Y') }}</td>
                        <td>
                            <div class="doc-action-btns">
                                <a href="{{ route('faculty.teaching-guides.view', $guide->id) }}" class="btn btn-action-view text-xs">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="{{ route('faculty.teaching-guides.download', $guide->id) }}" class="btn btn-action-download text-xs">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-gray-500 dark:text-gray-400 py-8">No approved teaching guides available yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-5">{{ $guides->links() }}</div>
        </div>
    </div>

    @if(isset($rejectedGuides) && $rejectedGuides->isNotEmpty())
    <div class="content-card mt-5">
        <div class="card-header">
            <h3 class="card-title">Rejected Submissions</h3>
            <span class="badge badge-danger">{{ $rejectedGuides->count() }}</span>
        </div>
        <div class="resource-list-table-wrap px-4 pb-4">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Subject</th>
                        <th>Remarks</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rejectedGuides as $guide)
                    <tr>
                        <td><strong>{{ $guide->title }}</strong></td>
                        <td><span class="doc-category-badge">{{ $guide->subject }}</span></td>
                        <td class="text-xs text-gray-500">{{ $guide->remarks ?? '—' }}</td>
                        <td>{{ $guide->created_at->format('M d, Y') }}</td>
                        <td>
                            <div class="doc-action-btns">
                                <a href="{{ route('faculty.teaching-guides.view', $guide->id) }}" class="btn btn-action-view text-xs"><i class="fas fa-eye"></i> View</a>
                                <a href="{{ route('faculty.teaching-guides.download', $guide->id) }}" class="btn btn-action-download text-xs"><i class="fas fa-download"></i> Download</a>
                                <form id="delete-tg-{{ $guide->id }}" action="{{ route('faculty.teaching-guides.destroy', $guide->id) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                </form>
                                <button type="button" onclick="confirmSubmissionDelete('tg', {{ $guide->id }})" class="btn btn-danger text-xs">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @include('partials.rename-submission-modal', [
        'renameRouteSample' => route('faculty.teaching-guides.rename', ['id' => 1]),
        'titleIdPrefix' => 'pending-tg-title-',
    ])
@endsection

@push('scripts')
<script>
function confirmSubmissionDelete(kind, id) {
    Swal.fire({
        title: 'Delete teaching guide?',
        text: 'This removes the rejected file from your list.',
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
