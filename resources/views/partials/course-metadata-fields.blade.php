<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
    <div><label class="form-label" for="{{ $fieldPrefix }}-year_level">Year level</label><select name="year_level" id="{{ $fieldPrefix }}-year_level" class="form-control"><option value="">Not specified</option>@foreach(range(1, 6) as $year)<option value="{{ $year }}" @selected($restoreInput && old('year_level') == $year)>Year {{ $year }}</option>@endforeach</select></div>
    <div><label class="form-label" for="{{ $fieldPrefix }}-semester">Semester</label><select name="semester" id="{{ $fieldPrefix }}-semester" class="form-control"><option value="">Not specified</option>@foreach(\App\Support\SchoolTerm::labels() as $value => $label)<option value="{{ $value }}" @selected($restoreInput && old('semester') === $value)>{{ $label }}</option>@endforeach</select></div>
    @foreach(['lecture_units' => 'Lecture units', 'lab_units' => 'Lab units'] as $field => $label)
        <div><label class="form-label" for="{{ $fieldPrefix }}-{{ $field }}">{{ $label }}</label><input type="number" min="0" max="99" step="0.01" name="{{ $field }}" id="{{ $fieldPrefix }}-{{ $field }}" value="{{ $restoreInput ? old($field) : '' }}" class="form-control" placeholder="Not specified"></div>
    @endforeach
</div>
