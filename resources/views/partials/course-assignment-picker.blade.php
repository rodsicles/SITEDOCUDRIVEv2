{{--
  Guided course assignment picker (Dean / Program Coordinator).
  Params:
    $pickerId (string) required unique id prefix
    $courses (Collection) required
    $selectedIds (array) optional
    $name (string) input name, default course_ids[]
    $required (bool) default false
    $hint (string|null) optional helper text
--}}
@php
    use App\Support\SchoolTerm;
    $pickerId = $pickerId ?? 'coursePicker';
    $courses = $courses ?? collect();
    $selectedIds = array_map('intval', (array) ($selectedIds ?? old('course_ids', [])));
    $name = $name ?? 'course_ids[]';
    $required = $required ?? false;
    $hint = $hint ?? null;
    $defaultTerm = SchoolTerm::current();
    $termLabels = SchoolTerm::labels();
@endphp

@if($courses->isNotEmpty())
<div class="course-assignment-guide" data-course-guide="{{ $pickerId }}" data-default-term="{{ $defaultTerm }}">
    @if($hint)
        <small class="text-xs text-gray-500 dark:text-gray-400 block mb-2">{!! $hint !!}</small>
    @endif

    <div class="course-guide-bar">
        <div class="course-guide-bar__term">
            <label class="course-guide-label" for="{{ $pickerId }}Term">Current term</label>
            <select id="{{ $pickerId }}Term" class="form-control course-guide-term" data-guide-term>
                @foreach($termLabels as $value => $label)
                    <option value="{{ $value }}" @selected($value === $defaultTerm)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="course-guide-bar__years" role="group" aria-label="Year level filter">
            <span class="course-guide-label">Year</span>
            <div class="course-guide-year-chips">
                <button type="button" class="course-guide-chip is-active" data-guide-year="" aria-pressed="true">All</button>
                @for($y = 1; $y <= 4; $y++)
                    <button type="button" class="course-guide-chip" data-guide-year="{{ $y }}" aria-pressed="false">{{ $y }}Y</button>
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
            <div class="course-checkbox-grid" id="{{ $pickerId }}Grid" data-guide-grid>
                @foreach($courses as $course)
                    @php $checked = in_array((int) $course->id, $selectedIds, true); @endphp
                    <label class="course-checkbox-item {{ $checked ? 'selected' : '' }}"
                           data-year="{{ $course->year_level ?? '' }}"
                           data-semester="{{ $course->semester ?? '' }}">
                        <input type="checkbox" name="{{ $name }}" value="{{ $course->id }}"
                               {{ $checked ? 'checked' : '' }}
                               @if($required) data-course-required="1" @endif>
                        <span>
                            <strong>{{ $course->code }}</strong> &ndash; {{ $course->title }}
                            @if($course->year_level || $course->semester)
                                <em class="course-term-tag">{{ $course->year_level ? $course->year_level.'Y' : '' }}{{ $course->year_level && $course->semester ? ' · ' : '' }}{{ $course->semester }}</em>
                            @endif
                        </span>
                    </label>
                @endforeach
            </div>
            <p class="course-no-results" id="{{ $pickerId }}NoResults" data-guide-empty>No matching courses for this term filter.</p>
        </div>
    </div>
</div>
@endif
