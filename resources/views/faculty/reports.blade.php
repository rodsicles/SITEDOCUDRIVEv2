@extends('layouts.dashboard')

@section('title', 'Reports')

@section('page-title', 'Reports')
@section('page-subtitle', 'Submit and review your reports')

@section('sidebar')
    @include('partials.faculty-sidebar')
@endsection

@section('content')
@php
    $reportTotal = $reports->total();
    $shouldOpenSubmit = $openSubmit || $errors->any();
@endphp

<div class="content-card faculty-reports-page">
    <div class="card-header">
        <h3 class="card-title">
            Submitted reports
            <span class="badge badge-info ml-1.5">{{ $reportTotal }}</span>
        </h3>
        <button type="button"
                class="btn btn-primary btn-sm"
                id="openSubmitReportBtn">
            <i class="fas fa-plus mr-1"></i> Submit report
        </button>
    </div>

    @if($reportTotal > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>Report Title</th>
                    <th>Category</th>
                    <th>File</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reports as $report)
                <tr>
                    <td>
                        <strong>{{ $report->report_title }}</strong>
                        @if($report->description)
                            <div class="faculty-reports-desc">{{ \Illuminate\Support\Str::limit($report->description, 90) }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-info">{{ $report->report_category ?? 'N/A' }}</span>
                    </td>
                    <td class="font-mono text-xs">{{ $report->displayFilename() }}</td>
                    <td>{{ $report->created_at->format('M d, Y h:i A') }}</td>
                    <td>
                        <a href="{{ route('reports.view', $report->report_id) }}" target="_blank" class="btn btn-primary py-1 px-2.5 text-xs mr-1">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <a href="{{ route('reports.download', $report->report_id) }}" class="btn btn-secondary py-1 px-2.5 text-xs">
                            <i class="fas fa-download"></i> Download
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="faculty-reports-pager">
            {{ $reports->links() }}
        </div>
    @else
        <p class="faculty-reports-empty">No reports submitted yet. Use Submit report to add one.</p>
    @endif
</div>

<div id="submitReportModal"
     class="modal-overlay{{ $shouldOpenSubmit ? ' active' : '' }}"
     @if(!$shouldOpenSubmit) hidden @endif
     role="dialog"
     aria-modal="true"
     aria-hidden="{{ $shouldOpenSubmit ? 'false' : 'true' }}"
     aria-labelledby="submitReportModalTitle"
     onclick="if(event.target===this)closeSubmitReportModal()">
    <div class="modal-card" role="document">
        <div class="modal-header">
            <h3 class="modal-title" id="submitReportModalTitle">
                <i class="fas fa-file-alt mr-2"></i> Submit report
            </h3>
            <button type="button" class="modal-close" onclick="closeSubmitReportModal()" aria-label="Close submit report dialog">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form action="{{ route('faculty.store-report') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                @if($errors->any())
                    <div class="faculty-reports-errors">
                        @foreach($errors->all() as $error)
                            <p class="m-0">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <div class="form-group">
                    <label class="form-label" for="reportTitle">Report title</label>
                    <input type="text"
                           name="report_title"
                           id="reportTitle"
                           class="form-control"
                           value="{{ old('report_title') }}"
                           required
                           maxlength="200"
                           autocomplete="off">
                </div>

                <div class="form-group">
                    <label class="form-label" for="reportCategory">Category</label>
                    <select name="report_category" id="reportCategory" class="form-control">
                        <option value="">Select a category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}" @selected(old('report_category') === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="reportDescription">Description <span class="profile-field-tag profile-field-tag--muted">Optional</span></label>
                    <textarea name="description"
                              id="reportDescription"
                              class="form-control"
                              rows="3"
                              maxlength="2000">{{ old('description') }}</textarea>
                </div>

                <div class="form-group mb-0">
                    <label class="form-label" for="reportFile">File</label>
                    <input type="file"
                           name="report_file"
                           id="reportFile"
                           class="form-control"
                           required
                           accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                    <small class="faculty-reports-hint">PDF or Word (.doc, .docx), max 10MB.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeSubmitReportModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane mr-1"></i> Submit
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openSubmitReportModal() {
    var modal = document.getElementById('submitReportModal');
    if (!modal) return;
    modal.hidden = false;
    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
    var title = document.getElementById('reportTitle');
    if (title) title.focus();
}

function closeSubmitReportModal() {
    var modal = document.getElementById('submitReportModal');
    if (!modal) return;
    modal.classList.remove('active');
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
}

document.getElementById('openSubmitReportBtn')?.addEventListener('click', openSubmitReportModal);
document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') closeSubmitReportModal();
});
</script>
@endpush
