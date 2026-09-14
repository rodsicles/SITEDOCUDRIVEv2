@extends('layouts.dashboard')

@section('title', 'Document Requests')
@section('page-title', 'Document Requests')
@section('page-subtitle', 'Request, submit, and track required employee documents')

@section('sidebar')
    @if(auth()->user()->isProgramCoordinator())
        @include('partials.coordinator-sidebar')
    @elseif(auth()->user()->isFaculty())
        @include('partials.faculty-sidebar')
    @elseif(auth()->user()->isSecretary())
        @include('partials.secretary-sidebar')
    @else
        @include('partials.dean-sidebar')
    @endif
@endsection

@section('content')
@php
    $viewer = auth()->user();
    $viewRoute = fn ($id) => route(($viewer->isProgramCoordinator() ? 'coordinator' : ($viewer->isFaculty() ? 'faculty' : 'dean')).'.view-document', $id);
@endphp
<div class="request-workspace">
    <div class="request-summary-strip">
        <div><span>Needs your action</span><strong>{{ $pendingCount }}</strong></div>
        <div><span>Received</span><strong>{{ $tab === 'received' ? $requests->total() : '—' }}</strong></div>
        <div><span>Sent</span><strong>{{ $tab === 'sent' ? $requests->total() : '—' }}</strong></div>
        <button type="button" class="btn btn-primary" data-open-modal="createRequestModal"><i class="fas fa-plus"></i> New request</button>
    </div>

    @if(session('success'))<div class="workflow-alert workflow-alert--success" role="status"><i class="fas fa-check-circle"></i>{{ session('success') }}</div>@endif
    @if($errors->any())<div class="workflow-alert workflow-alert--danger" role="alert"><i class="fas fa-circle-exclamation"></i>{{ $errors->first() }}</div>@endif

    <div class="request-toolbar">
        <nav class="request-tabs" aria-label="Request views">
            <a href="{{ route('document-requests.index', ['tab' => 'received']) }}" class="{{ $tab === 'received' ? 'active' : '' }}">Received</a>
            <a href="{{ route('document-requests.index', ['tab' => 'sent']) }}" class="{{ $tab === 'sent' ? 'active' : '' }}">Sent</a>
        </nav>
        <form method="GET" class="request-filter">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <label for="requestStatus">Status</label>
            <select id="requestStatus" name="status" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach(['pending','submitted','changes_requested','approved'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="request-list">
        @forelse($requests as $item)
            @php $myRecord = $item->recipients->firstWhere('user_id', $viewer->id); @endphp
            <article class="request-row">
                <div class="request-row__main">
                    <div class="request-row__heading">
                        <h2>{{ $item->title }}</h2>
                        @if($tab === 'received' && $myRecord)
                            <span class="request-status request-status--{{ $myRecord->status }}">{{ str($myRecord->status)->replace('_', ' ')->title() }}</span>
                        @else
                            <span class="request-status">{{ $item->recipients->where('status', 'approved')->count() }}/{{ $item->recipients->count() }} approved</span>
                        @endif
                    </div>
                    <p>{{ $item->instructions ?: 'No additional instructions.' }}</p>
                    <div class="request-meta">
                        <span><i class="fas fa-user"></i>{{ $item->requester->employee->full_name ?? $item->requester->username }}</span>
                        @if($item->course)<span><i class="fas fa-book"></i>{{ $item->course->code }}</span>@endif
                        @if($item->department)<span><i class="fas fa-building"></i>{{ $item->department }}</span>@endif
                        @if($item->schoolYear)<span><i class="fas fa-calendar"></i>{{ $item->schoolYear->name }}</span>@endif
                        <span class="{{ $item->due_at?->isPast() ? 'is-overdue' : '' }}"><i class="fas fa-clock"></i>{{ $item->due_at ? 'Due '.$item->due_at->format('M d, Y g:i A') : 'No deadline' }}</span>
                    </div>
                </div>

                @if($tab === 'received' && $myRecord)
                    <div class="request-row__action">
                        @if(in_array($myRecord->status, ['pending','changes_requested']))
                            @if($myRecord->review_note)<p class="request-review-note"><strong>Correction:</strong> {{ $myRecord->review_note }}</p>@endif
                            <button type="button" class="btn btn-primary request-submit-trigger" data-open-modal="submitRequestModal" data-recipient="{{ $myRecord->id }}" data-title="{{ $item->title }}" data-type="{{ $item->document_type }}">Submit document</button>
                        @elseif($myRecord->document)
                            <a class="btn btn-secondary" href="{{ $viewRoute($myRecord->document->document_id) }}"><i class="fas fa-eye"></i> View submission</a>
                        @endif
                    </div>
                @else
                    <div class="request-recipient-list">
                        @foreach($item->recipients as $record)
                        <div class="request-recipient">
                            <div><strong>{{ $record->recipient->employee->full_name ?? $record->recipient->username }}</strong><span>{{ str($record->status)->replace('_', ' ')->title() }}</span></div>
                            @if($record->status === 'submitted')
                            <div class="request-review-actions">
                                @if($record->document)<a href="{{ $viewRoute($record->document->document_id) }}" class="btn btn-secondary"><i class="fas fa-eye"></i> View</a>@endif
                                <form method="POST" action="{{ route('document-requests.review', $record) }}">@csrf<input type="hidden" name="decision" value="approved"><button class="btn btn-primary">Approve</button></form>
                                <button type="button" class="btn btn-secondary request-changes-trigger" data-open-modal="changesRequestModal" data-recipient="{{ $record->id }}" data-name="{{ $record->recipient->employee->full_name ?? $record->recipient->username }}">Request changes</button>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                @endif
            </article>
        @empty
            @include('partials.ui.empty-state', ['title' => 'No document requests here', 'text' => $tab === 'received' ? 'New requests assigned to you will appear here.' : 'Create a request to begin tracking compliance.'])
        @endforelse
    </div>
    {{ $requests->links() }}
</div>

<div id="createRequestModal" class="modal-overlay" hidden role="dialog" aria-modal="true" aria-labelledby="createRequestTitle">
    <div class="modal-card modal-card--wide request-modal">
        <div class="modal-header"><div><h2 id="createRequestTitle" class="modal-title">New document request</h2><p>Assign one requirement to one or more employees.</p></div><button type="button" class="modal-close" data-close-modal><i class="fas fa-times"></i></button></div>
        <form method="POST" action="{{ route('document-requests.store') }}">@csrf
            <div class="modal-body request-form-grid">
                <div class="form-group request-form-wide"><label class="form-label">Request title *</label><input class="form-control" name="title" value="{{ old('title') }}" maxlength="150" required placeholder="e.g. Updated faculty workload form"></div>
                <div class="form-group request-form-wide"><label class="form-label">Instructions</label><textarea class="form-control" name="instructions" maxlength="2000" rows="3" placeholder="Explain exactly what must be submitted.">{{ old('instructions') }}</textarea></div>
                <fieldset class="request-people request-form-wide"><legend>Recipients *</legend><input type="search" class="form-control request-people-search" placeholder="Find an employee…"><div class="request-people-list">@foreach($people as $person)<label data-search="{{ strtolower(($person->employee->full_name ?? $person->username).' '.($person->employee->department ?? '').' '.($person->role->role_name ?? '')) }}"><input type="checkbox" name="recipient_ids[]" value="{{ $person->id }}" data-course-ids='@json($person->assignedCourses->pluck('id')->values())' @checked(in_array($person->id, old('recipient_ids', [])))><span><strong>{{ $person->employee->full_name ?? $person->username }}</strong><small>{{ $person->role->role_name ?? 'Employee' }} · {{ $person->employee->department ?? 'No department' }}</small></span></label>@endforeach</div></fieldset>
                <div class="form-group"><label class="form-label">Accepted file type *</label><select class="form-control" name="document_type" required><option value="any">PDF, Word, or image</option><option value="pdf">PDF only</option><option value="word">Word only</option><option value="image">Image only</option></select></div>
                <div class="form-group"><label class="form-label">Due date</label><input class="form-control" type="datetime-local" name="due_at" min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}"></div>
                <div class="form-group request-form-wide"><label class="form-label" for="requestCourse">Course</label><select id="requestCourse" class="form-control" name="course_id" data-selected-course="{{ old('course_id') }}"><option value="">Not course-specific</option></select><small id="requestCourseHint" class="text-xs text-gray-500">Select recipients to see their assigned courses. You may leave this as not course-specific.</small></div>
                <div class="form-group"><label class="form-label">School year</label><select class="form-control" name="school_year_id"><option value="">Not specified</option>@foreach($schoolYears as $year)<option value="{{ $year->id }}">{{ $year->name }}</option>@endforeach</select></div>
                <div class="form-group"><label class="form-label">Semester</label><select class="form-control" name="semester"><option value="">Not specified</option><option value="1st">1st semester</option><option value="2nd">2nd semester</option></select></div>
                <label class="request-checkbox request-form-wide"><input type="checkbox" name="allow_late_submission" value="1" checked> Allow late submissions and mark them overdue</label>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-close-modal>Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send request</button></div>
        </form>
    </div>
</div>

<div id="submitRequestModal" class="modal-overlay" hidden role="dialog" aria-modal="true" aria-labelledby="submitRequestTitle">
    <div class="modal-card request-modal"><div class="modal-header"><div><h2 id="submitRequestTitle" class="modal-title">Submit requested document</h2><p id="submitRequestSubtitle"></p></div><button type="button" class="modal-close" data-close-modal><i class="fas fa-times"></i></button></div>
        <form id="requestSubmitForm" method="POST" enctype="multipart/form-data">@csrf<div class="modal-body"><label class="request-dropzone" for="requestFile"><i class="fas fa-cloud-arrow-up"></i><strong>Choose a file to submit</strong><span id="requestFileHint">Maximum 10 MB</span><input id="requestFile" type="file" name="file" required></label><div id="duplicateWarning" class="duplicate-warning" hidden></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-close-modal>Cancel</button><button class="btn btn-primary" type="submit"><i class="fas fa-upload"></i> Submit document</button></div></form>
    </div>
</div>

<div id="changesRequestModal" class="modal-overlay" hidden role="dialog" aria-modal="true" aria-labelledby="changesRequestTitle"><div class="modal-card request-modal"><div class="modal-header"><div><h2 id="changesRequestTitle" class="modal-title">Request changes</h2><p id="changesRequestSubtitle"></p></div><button type="button" class="modal-close" data-close-modal><i class="fas fa-times"></i></button></div><form id="changesRequestForm" method="POST">@csrf<input type="hidden" name="decision" value="changes_requested"><div class="modal-body"><label class="form-label">What needs to be corrected? *</label><textarea class="form-control" name="review_note" maxlength="1000" rows="4" required></textarea></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-close-modal>Cancel</button><button class="btn btn-primary">Send correction request</button></div></form></div></div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-open-modal]').forEach(function (button) {
    button.addEventListener('click', function () {
        var modal = document.getElementById(this.dataset.openModal);
        if (this.classList.contains('request-submit-trigger')) {
            document.getElementById('requestSubmitForm').action = @json(url('/document-request-recipients')) + '/' + this.dataset.recipient + '/submit';
            document.getElementById('submitRequestSubtitle').textContent = this.dataset.title;
            var accept = {pdf:'.pdf',word:'.doc,.docx',image:'.jpg,.jpeg,.png,.gif,.webp',any:'.pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.webp'};
            document.getElementById('requestFile').accept = accept[this.dataset.type] || accept.any;
        }
        if (this.classList.contains('request-changes-trigger')) {
            document.getElementById('changesRequestForm').action = @json(url('/document-request-recipients')) + '/' + this.dataset.recipient + '/review';
            document.getElementById('changesRequestSubtitle').textContent = 'Send clear feedback to ' + this.dataset.name + '.';
        }
        modal.hidden = false; modal.classList.add('active');
    });
});
document.querySelectorAll('[data-close-modal]').forEach(function (button) { button.addEventListener('click', function () { var modal = this.closest('.modal-overlay'); modal.classList.remove('active'); modal.hidden = true; }); });
document.querySelector('.request-people-search')?.addEventListener('input', function () { var q = this.value.toLowerCase(); document.querySelectorAll('.request-people-list label').forEach(function (row) { row.hidden = !row.dataset.search.includes(q); }); });

var requestCourses = @json($courses->mapWithKeys(fn ($course) => [(string) $course->id => $course->code.' — '.$course->title]));
var requestCourseSelect = document.getElementById('requestCourse');

function updateRequestCourseChoices() {
    if (!requestCourseSelect) return;

    var recipients = Array.from(document.querySelectorAll('.request-people-list input[name="recipient_ids[]"]:checked'));
    var previousValue = requestCourseSelect.value || requestCourseSelect.dataset.selectedCourse || '';
    var commonCourseIds = [];

    if (recipients.length) {
        commonCourseIds = JSON.parse(recipients[0].dataset.courseIds || '[]').map(String);
        recipients.slice(1).forEach(function (recipient) {
            var assignedIds = JSON.parse(recipient.dataset.courseIds || '[]').map(String);
            commonCourseIds = commonCourseIds.filter(function (courseId) { return assignedIds.includes(courseId); });
        });
    }

    requestCourseSelect.replaceChildren(new Option('Not course-specific', ''));
    commonCourseIds.forEach(function (courseId) {
        if (requestCourses[courseId]) requestCourseSelect.add(new Option(requestCourses[courseId], courseId));
    });
    requestCourseSelect.value = commonCourseIds.includes(String(previousValue)) ? String(previousValue) : '';
    requestCourseSelect.dataset.selectedCourse = '';

    var hint = document.getElementById('requestCourseHint');
    if (!hint) return;
    if (!recipients.length) {
        hint.textContent = 'Select recipients to see their assigned courses. You may leave this as not course-specific.';
    } else if (!commonCourseIds.length) {
        hint.textContent = recipients.length > 1
            ? 'The selected recipients have no assigned course in common. This request will be not course-specific.'
            : 'This recipient has no assigned courses. This request will be not course-specific.';
    } else {
        hint.textContent = recipients.length > 1
            ? 'Only courses assigned to every selected recipient are shown.'
            : 'Only courses assigned to this recipient are shown.';
    }
}

document.querySelectorAll('.request-people-list input[name="recipient_ids[]"]').forEach(function (checkbox) {
    checkbox.addEventListener('change', updateRequestCourseChoices);
});
updateRequestCourseChoices();

document.getElementById('requestFile')?.addEventListener('change', async function () {
    var warning = document.getElementById('duplicateWarning'); warning.hidden = true;
    if (!this.files[0] || !crypto.subtle) return;
    var hash = Array.from(new Uint8Array(await crypto.subtle.digest('SHA-256', await this.files[0].arrayBuffer()))).map(function (b) { return b.toString(16).padStart(2, '0'); }).join('');
    var response = await fetch(@json(route('document-search.duplicate')), {method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({hash:hash})});
    if (response.ok) { var data = await response.json(); if (data.duplicate) { warning.innerHTML = '<i class="fas fa-triangle-exclamation"></i><span>This appears to duplicate <strong></strong>. You may still submit if a new copy was requested.</span>'; warning.querySelector('strong').textContent = data.document; warning.hidden = false; } }
});
</script>
@endpush
