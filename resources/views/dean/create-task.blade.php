@extends('layouts.dashboard')

@section('title', 'Create Task')

@section('page-title', 'Create New Task')
@section('page-subtitle', 'Assign a task to coordinators or faculty members')

@section('sidebar')
    @include('partials.dean-sidebar')
@endsection

@section('content')
    <div class="max-w-2xl mx-auto">
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

            <div class="form-group" id="assigneeIdsGroup">
                <label class="form-label" for="assignee_ids">Select People</label>
                <select name="assignee_ids[]" id="assignee_ids" class="form-control" multiple size="8">
                    @foreach($assignableUsers as $user)
                        @php
                            $dept = $user->employee->department ?? '';
                            $label = ($user->employee->full_name ?? $user->username)
                                . ' (' . $user->username . ') — ' . ($user->role->role_name ?? '')
                                . ($dept ? ' — ' . $dept : '');
                        @endphp
                        <option value="{{ $user->id }}" @selected(collect(old('assignee_ids', []))->contains($user->id))>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <small class="text-gray-500 dark:text-gray-400 block mt-1">Hold Ctrl (Windows) or Cmd (Mac) to select multiple people.</small>
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
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var scope = document.getElementById('assignment_scope');
    var group = document.getElementById('assigneeIdsGroup');
    var select = document.getElementById('assignee_ids');

    function syncAssigneeField() {
        var isIndividual = scope.value === 'individual';
        group.style.display = isIndividual ? '' : 'none';
        if (isIndividual) {
            select.setAttribute('required', 'required');
        } else {
            select.removeAttribute('required');
            Array.from(select.options).forEach(function (opt) { opt.selected = false; });
        }
    }

    scope.addEventListener('change', syncAssigneeField);
    syncAssigneeField();
});
</script>
@endpush
