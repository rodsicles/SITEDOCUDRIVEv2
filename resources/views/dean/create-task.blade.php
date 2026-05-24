@extends('layouts.dashboard')

@section('title', 'Create Task')

@section('page-title', 'Create New Task')
@section('page-subtitle', 'Assign a task to coordinators or faculty members')

@section('sidebar')
    @include('partials.dean-sidebar')
@endsection

@php
    $assigneePickerData = $assignableUsers->map(fn ($u) => [
        'id' => (int) $u->id,
        'name' => $u->employee->full_name ?? $u->username,
        'role' => $u->role->role_name ?? '',
        'department' => $u->employee->department ?? '',
    ])->values();
    $oldAssigneeIds = collect(old('assignee_ids', []))->map(fn ($id) => (int) $id)->filter()->values()->all();
@endphp

@section('content')
    <div class="max-w-4xl mx-auto">
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Task Details</h3>
        </div>

        <form action="{{ route('dean.store-task') }}" method="POST" enctype="multipart/form-data" id="createTaskForm">
            @csrf

            <div class="form-group">
                <label class="form-label" for="assignment_scope">Assign To</label>
                <select name="assignment_scope" id="assignment_scope" class="form-control" required>
                    <option value="individual" @selected(old('assignment_scope', 'individual') === 'individual')>Selected faculty / coordinators</option>
                    <option value="department_it" @selected(old('assignment_scope') === 'department_it')>All Information Technology</option>
                    <option value="department_engineering" @selected(old('assignment_scope') === 'department_engineering')>All Engineering</option>
                    <option value="department_site" @selected(old('assignment_scope') === 'department_site')>Whole SITE (IT + Engineering)</option>
                </select>
                <small class="text-gray-500 dark:text-gray-400 block mt-1">
                    Department options assign one copy of this task to every active faculty and coordinator in that group.
                </small>
            </div>

            <div class="form-group task-assignee-picker-wrap" id="assigneePickerGroup">
                <label class="form-label" for="assignee_filter_department">Department</label>
                <select id="assignee_filter_department" class="form-control" aria-label="Filter faculty by department">
                    <option value="">Choose department to browse faculty</option>
                    <option value="{{ \App\Support\TaskAssigneeResolver::DEPARTMENT_IT }}">{{ \App\Support\TaskAssigneeResolver::DEPARTMENT_IT }}</option>
                    <option value="{{ \App\Support\TaskAssigneeResolver::DEPARTMENT_ENGINEERING }}">{{ \App\Support\TaskAssigneeResolver::DEPARTMENT_ENGINEERING }}</option>
                </select>

                <div class="task-assignee-picker mt-3">
                    <div class="task-assignee-picker__panel">
                        <div class="task-assignee-picker__panel-head">
                            <span>Available</span>
                            <span class="task-assignee-picker__hint" id="assigneeAvailableHint">Select a department</span>
                        </div>
                        <div class="task-assignee-picker__list" id="assigneeAvailableList" role="listbox" aria-label="Available faculty and coordinators"></div>
                    </div>

                    <div class="task-assignee-picker__actions" aria-hidden="true">
                        <button type="button" class="btn btn-primary task-assignee-picker__action-btn" id="assigneeAddBtn" disabled title="Add selected">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <button type="button" class="btn bg-gray-600 text-white task-assignee-picker__action-btn" id="assigneeAddAllBtn" disabled title="Add all shown">
                            <i class="fas fa-angle-double-right"></i>
                        </button>
                        <button type="button" class="btn bg-gray-600 text-white task-assignee-picker__action-btn" id="assigneeRemoveBtn" disabled title="Remove selected from assignees">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                    </div>

                    <div class="task-assignee-picker__panel task-assignee-picker__panel--selected">
                        <div class="task-assignee-picker__panel-head">
                            <span>Selected assignees</span>
                            <span class="task-assignee-picker__count" id="assigneeSelectedCount">0</span>
                        </div>
                        <div class="task-assignee-picker__list task-assignee-picker__list--selected" id="assigneeSelectedList" role="listbox" aria-label="Selected assignees"></div>
                        <p class="task-assignee-picker__empty" id="assigneeSelectedEmpty">No one selected yet. They will receive the task notification.</p>
                    </div>
                </div>

                <div id="assigneeHiddenInputs" class="sr-only" aria-hidden="true"></div>

                <small class="text-gray-500 dark:text-gray-400 block mt-2">
                    Choose a department, pick people from the left, then use the arrow to add them to Selected assignees.
                </small>
                @error('assignee_ids')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="task_title">Task Title</label>
                <input type="text" name="task_title" id="task_title" class="form-control"
                       placeholder="Enter task title" required maxlength="50"
                       value="{{ old('task_title') }}">
                <small class="text-gray-500 dark:text-gray-400 block mt-1">Maximum 50 characters.</small>
            </div>

            <div class="form-group">
                <label class="form-label" for="task_description">Task Description</label>
                <textarea name="task_description" id="task_description" class="form-control" rows="5"
                          placeholder="Enter detailed task description" maxlength="250">{{ old('task_description') }}</textarea>
                <small class="text-gray-500 dark:text-gray-400 block mt-1">Maximum 250 characters.</small>
            </div>

            <div class="form-group">
                <label class="form-label" for="due_date">Due Date</label>
                <input type="date" name="due_date" id="due_date" class="form-control" required
                       min="{{ date('Y') . '-01-01' }}" max="{{ date('Y') . '-12-31' }}"
                       value="{{ old('due_date') }}">
            </div>

            <div class="form-group">
                <label class="form-label">Task Attachments</label>
                <input type="file" name="attachments[]" class="form-control" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.jpg,.jpeg,.png" data-dropzone="1">
                <small class="text-gray-500 dark:text-gray-400 block mt-1">Optional. Upload up to 5 reference files (included in each assignee’s notification).</small>
            </div>

            <div class="flex gap-2.5">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create Task
                </button>
                <a href="{{ route('dean.tasks') }}" class="btn bg-gray-600 text-white">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
    </div>

    <script type="application/json" id="assignableUsersJson">{!! json_encode($assigneePickerData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
    <script type="application/json" id="oldAssigneeIdsJson">{!! json_encode($oldAssigneeIds) !!}</script>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var scope = document.getElementById('assignment_scope');
    var pickerGroup = document.getElementById('assigneePickerGroup');
    var form = document.getElementById('createTaskForm');
    var deptFilter = document.getElementById('assignee_filter_department');
    var availableList = document.getElementById('assigneeAvailableList');
    var selectedList = document.getElementById('assigneeSelectedList');
    var selectedEmpty = document.getElementById('assigneeSelectedEmpty');
    var selectedCountEl = document.getElementById('assigneeSelectedCount');
    var availableHint = document.getElementById('assigneeAvailableHint');
    var hiddenInputs = document.getElementById('assigneeHiddenInputs');
    var addBtn = document.getElementById('assigneeAddBtn');
    var addAllBtn = document.getElementById('assigneeAddAllBtn');
    var removeBtn = document.getElementById('assigneeRemoveBtn');

    var allUsers = [];
    try {
        allUsers = JSON.parse(document.getElementById('assignableUsersJson').textContent || '[]');
    } catch (e) {
        allUsers = [];
    }

    var initialSelected = [];
    try {
        initialSelected = JSON.parse(document.getElementById('oldAssigneeIdsJson').textContent || '[]');
    } catch (e) {
        initialSelected = [];
    }

    var selectedIds = new Set(initialSelected.map(Number));
    var highlightedAvailableId = null;
    var highlightedSelectedId = null;

    function roleShort(role) {
        if (role === 'Program Coordinator') return 'Coordinator';
        if (role === 'Faculty Employee') return 'Faculty';
        return role || '';
    }

    function syncHiddenInputs() {
        hiddenInputs.innerHTML = '';
        selectedIds.forEach(function (id) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'assignee_ids[]';
            input.value = String(id);
            hiddenInputs.appendChild(input);
        });
    }

    function updateCounts() {
        var count = selectedIds.size;
        selectedCountEl.textContent = String(count);
        selectedEmpty.style.display = count === 0 ? '' : 'none';
        selectedList.style.display = count === 0 ? 'none' : '';
    }

    function usersForDepartment(dept) {
        if (!dept) return [];
        return allUsers.filter(function (u) {
            return u.department === dept && !selectedIds.has(u.id);
        });
    }

    function renderAvailable() {
        var dept = deptFilter.value;
        availableList.innerHTML = '';
        highlightedAvailableId = null;
        addBtn.disabled = true;
        addAllBtn.disabled = true;

        if (!dept) {
            availableHint.textContent = 'Select a department';
            var placeholder = document.createElement('p');
            placeholder.className = 'task-assignee-picker__placeholder';
            placeholder.textContent = 'Choose Information Technology or Engineering above.';
            availableList.appendChild(placeholder);
            return;
        }

        var pool = usersForDepartment(dept);
        availableHint.textContent = pool.length + ' available';

        if (pool.length === 0) {
            var empty = document.createElement('p');
            empty.className = 'task-assignee-picker__placeholder';
            empty.textContent = 'Everyone in this department is already selected, or none are listed.';
            availableList.appendChild(empty);
            return;
        }

        addAllBtn.disabled = false;

        pool.forEach(function (user) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'task-assignee-picker__item';
            btn.dataset.userId = String(user.id);
            btn.setAttribute('role', 'option');

            var name = document.createElement('span');
            name.className = 'task-assignee-picker__item-name';
            name.textContent = user.name;

            var meta = document.createElement('span');
            meta.className = 'task-assignee-picker__item-meta';
            meta.textContent = roleShort(user.role);

            btn.appendChild(name);
            btn.appendChild(meta);

            btn.addEventListener('click', function () {
                highlightedAvailableId = user.id;
                highlightedSelectedId = null;
                renderAvailableHighlight();
                renderSelectedHighlight();
                addBtn.disabled = false;
                removeBtn.disabled = true;
            });

            btn.addEventListener('dblclick', function () {
                addUser(user.id);
            });

            availableList.appendChild(btn);
        });

        renderAvailableHighlight();
    }

    function renderAvailableHighlight() {
        availableList.querySelectorAll('.task-assignee-picker__item').forEach(function (el) {
            el.classList.toggle('is-active', Number(el.dataset.userId) === highlightedAvailableId);
        });
    }

    function renderSelected() {
        selectedList.innerHTML = '';
        highlightedSelectedId = null;
        removeBtn.disabled = true;

        var selected = allUsers.filter(function (u) { return selectedIds.has(u.id); });
        selected.sort(function (a, b) { return a.name.localeCompare(b.name); });

        selected.forEach(function (user) {
            var row = document.createElement('button');
            row.type = 'button';
            row.className = 'task-assignee-picker__item task-assignee-picker__item--selected';
            row.dataset.userId = String(user.id);
            row.setAttribute('role', 'option');

            var name = document.createElement('span');
            name.className = 'task-assignee-picker__item-name';
            name.textContent = user.name;

            var meta = document.createElement('span');
            meta.className = 'task-assignee-picker__item-meta';
            meta.textContent = roleShort(user.role) + ' · ' + (user.department || '');

            var removeIcon = document.createElement('span');
            removeIcon.className = 'task-assignee-picker__remove';
            removeIcon.setAttribute('aria-hidden', 'true');
            removeIcon.innerHTML = '<i class="fas fa-times"></i>';

            row.appendChild(name);
            row.appendChild(meta);
            row.appendChild(removeIcon);

            row.addEventListener('click', function (e) {
                if (e.target.closest('.task-assignee-picker__remove')) {
                    removeUser(user.id);
                    return;
                }
                highlightedSelectedId = user.id;
                highlightedAvailableId = null;
                renderAvailableHighlight();
                renderSelectedHighlight();
                addBtn.disabled = true;
                removeBtn.disabled = false;
            });

            selectedList.appendChild(row);
        });

        renderSelectedHighlight();
        updateCounts();
        syncHiddenInputs();
    }

    function renderSelectedHighlight() {
        selectedList.querySelectorAll('.task-assignee-picker__item').forEach(function (el) {
            el.classList.toggle('is-active', Number(el.dataset.userId) === highlightedSelectedId);
        });
    }

    function addUser(id) {
        selectedIds.add(Number(id));
        highlightedAvailableId = null;
        renderAvailable();
        renderSelected();
    }

    function removeUser(id) {
        selectedIds.delete(Number(id));
        highlightedSelectedId = null;
        renderAvailable();
        renderSelected();
    }

    function addHighlighted() {
        if (highlightedAvailableId != null) {
            addUser(highlightedAvailableId);
        }
    }

    function addAllVisible() {
        var dept = deptFilter.value;
        usersForDepartment(dept).forEach(function (u) {
            selectedIds.add(u.id);
        });
        renderAvailable();
        renderSelected();
    }

    function removeHighlighted() {
        if (highlightedSelectedId != null) {
            removeUser(highlightedSelectedId);
        }
    }

    function syncScopeVisibility() {
        var isIndividual = scope.value === 'individual';
        pickerGroup.style.display = isIndividual ? '' : 'none';
        deptFilter.disabled = !isIndividual;
        if (!isIndividual) {
            selectedIds.clear();
            syncHiddenInputs();
            renderSelected();
        }
    }

    deptFilter.addEventListener('change', renderAvailable);
    addBtn.addEventListener('click', addHighlighted);
    addAllBtn.addEventListener('click', addAllVisible);
    removeBtn.addEventListener('click', removeHighlighted);
    scope.addEventListener('change', syncScopeVisibility);

    form.addEventListener('submit', function (e) {
        syncHiddenInputs();
        if (scope.value === 'individual' && selectedIds.size === 0) {
            e.preventDefault();
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'No assignees selected',
                    text: 'Add at least one person to Selected assignees before creating the task.',
                    icon: 'warning',
                    confirmButtonColor: '#028a0f',
                    customClass: { popup: 'swal-flat' }
                });
            } else {
                alert('Add at least one person to Selected assignees before creating the task.');
            }
        }
    });

    syncScopeVisibility();
    renderAvailable();
    renderSelected();
});
</script>
@endpush
