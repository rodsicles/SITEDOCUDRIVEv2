{{--
  Create-account form shared by the Dean (Coordinator + Faculty tabs) and the Program Coordinator (Faculty only).
  Params:
    $formKey (string) 'coordinator' | 'faculty'; also used for old-input scoping
    $action (string) form action URL
    $coursesUrl (string) JSON endpoint returning subjects for ?dept=
    $numberPreview (array) program code => next employee number
    $defaultProgram (string|null) program preselected on a fresh form
    $title, $intro (string)
    $submitLabel, $submitIcon (string)
    $cancelUrl (string|null) or $cancelOnclick (string|null)
    $numberExample (string) e.g. SITE-IT-FAC001
--}}
@php
    use App\Models\Employee;
    use App\Models\Program;

    $isFaculty = $formKey === 'faculty';
    $isOld = old('_form') === $formKey;
    $program = $isOld ? old('program') : ($defaultProgram ?? null);
    $selectedIds = $isOld ? (array) old('course_ids', []) : [];
    $prefix = $formKey;
    $roleNoun = $isFaculty ? 'faculty member' : 'coordinator';
@endphp

<div class="content-card employee-account-card">
    <div class="card-header">
        <div>
            <h3 class="card-title">{{ $title }}</h3>
            <p class="employee-account-card__intro">{{ $intro }}</p>
        </div>
    </div>

    @if($isOld && $errors->any())
        <div class="alert alert-error">
            <strong><i class="fas fa-exclamation-circle"></i> Please fix the following:</strong>
            <ul class="mt-2 ml-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ $action }}" method="POST" class="account-form" data-account-form="{{ $formKey }}">
        @csrf
        <input type="hidden" name="_form" value="{{ $formKey }}">

        <div class="account-form__grid">
            <div class="account-form__col">
                <div class="ui-form-section">
                    <h4 class="ui-form-section__title">Employee details</h4>
                    <div class="form-group">
                        <label class="form-label" for="{{ $prefix }}FullName">Full Name *</label>
                        <input type="text" id="{{ $prefix }}FullName" name="full_name" class="form-control" placeholder="Enter full name" required maxlength="45" value="{{ $isOld ? old('full_name') : '' }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="{{ $prefix }}Department">Program *</label>
                        <select id="{{ $prefix }}Department" name="program" class="form-control" required>
                            <option value="">Select Program</option>
                            @foreach(Program::labels() as $code => $label)
                                <option value="{{ $code }}" @selected($program === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if($isFaculty)
                        <div class="form-group">
                            <label class="form-label" for="{{ $prefix }}Type">Faculty Type *</label>
                            <select id="{{ $prefix }}Type" name="faculty_type" class="form-control" required>
                                <option value="">Select Faculty Type</option>
                                @foreach(Employee::FACULTY_TYPES as $value => $label)
                                    <option value="{{ $value }}" @selected($isOld && old('faculty_type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <small class="text-xs text-gray-500 dark:text-gray-400 mt-1">Shared faculty keeps one home program while teaching assigned subjects.</small>
                        </div>
                    @endif
                    <div class="form-group">
                        <label class="form-label" for="{{ $prefix }}EmployeeNo">Employee Number</label>
                        <input type="text" id="{{ $prefix }}EmployeeNo" class="form-control bg-gray-100 dark:bg-gray-800"
                               value="{{ $program ? ($numberPreview[$program] ?? '') : '' }}" placeholder="Select program first" readonly
                               data-number-preview='@json($numberPreview ?? [])' data-program-select="{{ $prefix }}Department">
                        <small class="text-xs text-gray-500 dark:text-gray-400 mt-1">Auto-generated per program (e.g. {{ $numberExample }}). Existing numbers are not changed.</small>
                    </div>
                </div>
            </div>
            <div class="account-form__col">
                <div class="ui-form-section">
                    <h4 class="ui-form-section__title">Account access</h4>
                    <div class="form-group">
                        <label class="form-label" for="{{ $prefix }}Username">Username *</label>
                        <input type="text" id="{{ $prefix }}Username" name="username" class="form-control" autocomplete="off" autocapitalize="none" spellcheck="false" placeholder="Choose a username" required maxlength="20" value="{{ $isOld ? old('username') : '' }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="{{ $prefix }}Password">Password *</label>
                        <input type="password" id="{{ $prefix }}Password" name="password" class="form-control" autocomplete="new-password" placeholder="Minimum 8 characters" required minlength="8" maxlength="40">
                    </div>
                </div>
            </div>
        </div>

        @include('partials.course-assignment-picker', [
            'pickerId' => $prefix.'Courses',
            'program' => $program,
            'selectedIds' => $selectedIds,
            'programSelect' => $prefix.'Department',
            'coursesUrl' => $coursesUrl,
            'required' => true,
            'showErrors' => $isOld,
            'hint' => 'Select the subjects this '.$roleNoun.' will handle. Defaults to the current school term; unlock to include other terms. At least one subject is required when the program has a curriculum.',
        ])

        <div class="account-form__actions">
            <button type="submit" class="btn btn-success">
                <i class="fas {{ $submitIcon }}"></i> {{ $submitLabel }}
            </button>
            @if(!empty($cancelUrl))
                <a href="{{ $cancelUrl }}" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
            @else
                <button type="button" class="btn btn-secondary" onclick="{{ $cancelOnclick ?? 'history.back()' }}">
                    <i class="fas fa-times"></i> Cancel
                </button>
            @endif
        </div>
    </form>
</div>
