{{--
  Guided course assignment picker (Dean / Program Coordinator, create + edit).
  Params:
    $pickerId (string) required unique id prefix
    $program (string|null) program whose subjects are rendered
    $courses (Collection|null) subjects for $program; loaded via CourseAssignment when omitted
    $selectedIds (array) optional
    $programSelect (string|null) id of the program <select>; subjects reload when it changes
    $coursesUrl (string|null) JSON endpoint returning subjects for ?dept=
    $name (string) input name, default course_ids[]
    $label (string) field label
    $required (bool) require one subject when the program has a curriculum
    $hint (string|null) optional helper text (HTML allowed)
    $showErrors (bool) render course_ids validation errors, default true
--}}
@php
    use App\Models\Program;
    use App\Support\CourseAssignment;
    use App\Support\SchoolTerm;

    $pickerId = $pickerId ?? 'coursePicker';
    $program = CourseAssignment::validProgram($program ?? null);
    $courses = $courses ?? CourseAssignment::coursesFor($program);
    $selectedIds = array_map('intval', (array) ($selectedIds ?? old('course_ids', [])));
    $programSelect = $programSelect ?? null;
    $coursesUrl = $coursesUrl ?? null;
    $name = $name ?? 'course_ids[]';
    $label = $label ?? 'Assigned subjects';
    $required = $required ?? false;
    $hint = $hint ?? null;
    $showErrors = $showErrors ?? true;
    $defaultTerm = SchoolTerm::current();
    $termLabels = SchoolTerm::labels();
    $programLabel = $program ? (Program::OPTIONS[$program] ?? $program) : null;
    $errorMessage = $showErrors ? ($errors->first('course_ids') ?: $errors->first('course_ids.*')) : '';
@endphp

<div class="account-form__courses form-group account-course-picker" data-course-picker-wrap>
    <label class="form-label" for="{{ $pickerId }}Search">{{ $label }}@if($required) *@endif</label>
    @if($hint)
        <small class="text-xs text-gray-500 dark:text-gray-400 block mb-2">{!! $hint !!}</small>
    @endif

    <div class="course-assignment-guide {{ $courses->isEmpty() ? 'is-empty' : '' }}"
         data-course-guide="{{ $pickerId }}"
         data-default-term="{{ $defaultTerm }}"
         data-program="{{ $program ?? '' }}"
         data-required="{{ $required ? '1' : '0' }}"
         @if($programSelect && $coursesUrl) data-program-select="{{ $programSelect }}" data-courses-url="{{ $coursesUrl }}" @endif>

        <div class="course-guide-bar">
            <div class="course-guide-bar__term">
                <label class="course-guide-label" for="{{ $pickerId }}Term">Current term</label>
                <select id="{{ $pickerId }}Term" class="form-control course-guide-term" data-guide-term>
                    @foreach($termLabels as $value => $termLabel)
                        <option value="{{ $value }}" @selected($value === $defaultTerm)>{{ $termLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="course-guide-bar__years" role="group" aria-label="Year level filter">
                <span class="course-guide-label">Year</span>
                <div class="course-guide-year-chips">
                    <button type="button" class="course-guide-chip is-active" data-guide-year="" aria-pressed="true">All</button>
                    @for($y = 1; $y <= 5; $y++)
                        <button type="button" class="course-guide-chip" data-guide-year="{{ $y }}" aria-pressed="false"{{ $y === 5 && ! $courses->contains('year_level', 5) ? ' hidden' : '' }}>{{ $y }}Y</button>
                    @endfor
                </div>
            </div>
            <label class="course-guide-unlock">
                <input type="checkbox" data-guide-unlock>
                <span>Also show courses from other terms</span>
            </label>
        </div>

        <div class="course-picker-wrap">
            <div class="course-picker-toolbar">
                <input type="text" id="{{ $pickerId }}Search" class="course-search-input" data-guide-search
                       placeholder="Search by code or title..." autocomplete="off">
                <span class="course-selected-count" id="{{ $pickerId }}Count" data-guide-count>0 selected</span>
                <button type="button" class="course-picker-clear" id="{{ $pickerId }}Clear" data-guide-clear>Clear</button>
            </div>
            <div class="course-picker-body">
                <div class="course-checkbox-grid" id="{{ $pickerId }}Grid" data-guide-grid data-input-name="{{ $name }}">
                    @if(! $program)
                        <span class="course-section-empty">Select a program to load its subjects.</span>
                    @elseif($courses->isEmpty())
                        <span class="course-section-empty">No curriculum has been set up for {{ $programLabel }} yet. You can save now and assign subjects after courses are added in the Course Catalog.</span>
                    @else
                        @foreach($courses as $course)
                            @php $checked = in_array((int) $course->id, $selectedIds, true); @endphp
                            <label class="course-checkbox-item {{ $checked ? 'selected' : '' }}"
                                   data-year="{{ $course->year_level ?? '' }}"
                                   data-semester="{{ $course->semester ?? '' }}">
                                <input type="checkbox" name="{{ $name }}" value="{{ $course->id }}" @checked($checked)>
                                <span>
                                    <strong>{{ $course->code }}</strong> &ndash; {{ $course->title }}
                                    @if($course->year_level || $course->semester)
                                        <em class="course-term-tag">{{ $course->year_level ? $course->year_level.'Y' : '' }}{{ $course->year_level && $course->semester ? ' · ' : '' }}{{ $course->semester }}</em>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    @endif
                </div>
                <p class="course-no-results" id="{{ $pickerId }}NoResults" data-guide-empty>No matching courses for this term filter.</p>
            </div>
        </div>
    </div>

    <p class="text-xs text-red-600 dark:text-red-400 mt-1 {{ $errorMessage ? '' : 'hidden' }}" data-guide-error role="alert">{{ $errorMessage }}</p>
</div>
