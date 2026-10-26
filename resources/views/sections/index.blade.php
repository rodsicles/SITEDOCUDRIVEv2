@extends('layouts.dashboard')
@section('title', 'Sections — Course Catalog')
@section('page-title', 'Course Catalog')
@section('page-subtitle', 'Manage sections used in Teacher’s Load')
@section('sidebar')
    @include('partials.'.$routePrefix.'-sidebar')
@endsection
@section('content')
@include('partials.catalog-tabs')
@if(session('success'))<div class="workflow-alert workflow-alert--success" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="workflow-alert workflow-alert--danger" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="content-card mb-4">
    <div class="card-header"><h2 class="card-title">Add section</h2></div>
    <form class="p-4" method="POST" action="{{ route('course-sections.store') }}">@csrf
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div><label class="form-label" for="section-program">Program *</label><select id="section-program" class="form-control" name="program" required>@foreach($programs as $program)<option @selected(old('program') === $program)>{{ $program }}</option>@endforeach</select></div>
            <div><label class="form-label" for="section-year">Year level *</label><select id="section-year" class="form-control" name="year_level" required>@foreach(range(1,4) as $year)<option value="{{ $year }}" @selected(old('year_level') == $year)>Year {{ $year }}</option>@endforeach</select></div>
            <div><label class="form-label" for="section-name">Section name *</label><input id="section-name" class="form-control" name="name" value="{{ old('name') }}" placeholder="e.g. BSIT 1 A" maxlength="40" required></div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Add section</button>
    </form>
</div>
<div class="content-card">
    <div class="card-header"><h2 class="card-title">Sections</h2></div>
    <form method="GET" class="p-4 flex gap-3 flex-wrap items-end">
        <input type="hidden" name="view" value="sections">
        <div><label class="form-label" for="sections-search">Search</label><input id="sections-search" class="form-control" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search section name"></div>
        <div><label class="form-label" for="sections-program">Program</label><select id="sections-program" class="form-control" name="program"><option value="all">All allowed programs</option>@foreach($programs as $program)<option @selected(($filters['program'] ?? '') === $program)>{{ $program }}</option>@endforeach</select></div>
        <div><label class="form-label" for="sections-status">Status</label><select id="sections-status" class="form-control" name="status">@foreach(['active'=>'Active', 'inactive'=>'Inactive', 'all'=>'All statuses'] as $value=>$label)<option value="{{ $value }}" @selected(($filters['status'] ?? 'active') === $value)>{{ $label }}</option>@endforeach</select></div>
        <button class="btn btn-secondary">Apply filters</button>
    </form>
    <p class="px-4 pb-3 text-sm">Remove hides a section from new choices. Rename and removal never rewrite saved teaching loads.</p>
    <div class="course-catalog-table-wrap"><table class="data-table"><thead><tr><th>Section</th><th>Program</th><th>Year level</th><th>Status</th><th>Actions</th></tr></thead><tbody>
    @forelse($sections as $section)
        <tr><td>{{ $section->name }}</td><td>{{ $section->program }}</td><td>{{ $section->year_level }}</td><td>{{ $section->is_active ? 'Active' : 'Inactive' }}</td><td><div class="flex gap-2 flex-wrap">
            <button type="button" class="btn btn-secondary" data-edit-section="{{ $section->id }}" data-name="{{ $section->name }}" data-year="{{ $section->year_level }}" data-version="{{ $section->lock_version }}">Rename / Edit</button>
            <button type="button" class="btn {{ $section->is_active ? 'btn-danger' : 'btn-secondary' }}" data-status-section="{{ $section->id }}" data-name="{{ $section->name }}" data-active="{{ $section->is_active ? '0' : '1' }}" data-version="{{ $section->lock_version }}">{{ $section->is_active ? 'Remove' : 'Restore' }}</button>
        </div></td></tr>
    @empty<tr><td colspan="5">No sections match this view. Add a section above or check Inactive.</td></tr>@endforelse
    </tbody></table></div><div class="p-4">{{ $sections->links() }}</div>
</div>
<dialog class="ui-dialog" id="section-edit-dialog" aria-labelledby="section-edit-title"><form method="POST" id="section-edit-form">@csrf @method('PATCH')
    <div class="ui-dialog__head"><h2 id="section-edit-title" class="ui-dialog__title">Edit section</h2><button type="button" class="ui-dialog__close" data-close-section aria-label="Close">×</button></div>
    <div class="ui-dialog__body"><input type="hidden" name="lock_version" id="section-edit-version"><input type="hidden" name="_section_id" id="section-edit-id">
        <label class="form-label" for="section-edit-name">Section name *</label><input class="form-control" id="section-edit-name" name="name" maxlength="40" required>
        <label class="form-label mt-3" for="section-edit-year">Year level *</label><select class="form-control" name="year_level" id="section-edit-year">@foreach(range(1,4) as $year)<option value="{{ $year }}">Year {{ $year }}</option>@endforeach</select>
        <p class="text-sm mt-3">Existing teaching loads retain their saved section names.</p>
    </div><div class="ui-dialog__foot"><button type="button" class="btn btn-secondary" data-close-section>Cancel</button><button class="btn btn-primary">Save section</button></div>
</form></dialog>
<dialog class="ui-dialog" id="section-status-dialog" aria-labelledby="section-status-title"><form method="POST" id="section-status-form">@csrf @method('PATCH')
    <div class="ui-dialog__head"><h2 id="section-status-title" class="ui-dialog__title">Change section availability</h2><button type="button" class="ui-dialog__close" data-close-section aria-label="Close">×</button></div>
    <div class="ui-dialog__body"><p id="section-status-message"></p><input type="hidden" name="lock_version" id="section-status-version"><input type="hidden" name="is_active" id="section-status-active"></div>
    <div class="ui-dialog__foot"><button type="button" class="btn btn-secondary" data-close-section>Cancel</button><button class="btn btn-primary" id="section-status-confirm">Confirm</button></div>
</form></dialog>
@endsection
@push('scripts')
<script>
const sectionBase = @json(url('/course-sections'));
document.querySelectorAll('[data-edit-section]').forEach(button => button.addEventListener('click', () => {
    document.getElementById('section-edit-form').action = sectionBase + '/' + button.dataset.editSection;
    document.getElementById('section-edit-id').value = button.dataset.editSection;
    document.getElementById('section-edit-name').value = button.dataset.name;
    document.getElementById('section-edit-year').value = button.dataset.year;
    document.getElementById('section-edit-version').value = button.dataset.version;
    document.getElementById('section-edit-dialog').showModal();
}));
document.querySelectorAll('[data-status-section]').forEach(button => button.addEventListener('click', () => {
    document.getElementById('section-status-form').action = sectionBase + '/' + button.dataset.statusSection + '/status';
    document.getElementById('section-status-active').value = button.dataset.active;
    document.getElementById('section-status-version').value = button.dataset.version;
    document.getElementById('section-status-message').textContent = (button.dataset.active === '1' ? 'Restore ' : 'Remove ') + button.dataset.name + '? Saved teaching loads will not change.';
    document.getElementById('section-status-confirm').className = 'btn ' + (button.dataset.active === '1' ? 'btn-primary' : 'btn-danger');
    document.getElementById('section-status-dialog').showModal();
}));
document.querySelectorAll('[data-close-section]').forEach(button => button.addEventListener('click', () => button.closest('dialog').close()));
@if($errors->any() && old('_section_id'))
const failedSection = document.querySelector('[data-edit-section="{{ (int) old('_section_id') }}"]');
if (failedSection) { failedSection.click(); document.getElementById('section-edit-name').value = @json(old('name')); document.getElementById('section-edit-year').value = @json(old('year_level')); }
@endif
</script>
@endpush
