{{--
    Structured upload fields: School Year → Semester → TG/LB (teaching guides)
    @param string $mode 'teaching-guide' | 'exam-questionnaire'
--}}
@php
    use App\Support\AcademicYear;
    $mode = $mode ?? 'teaching-guide';
    $startYear = (int) old('academic_year_start', AcademicYear::currentStartYear());
    $semester = old('semester', AcademicYear::currentSemester());
    $guideType = old('guide_type', 'teaching-guides');
    $activeStart = \App\Models\SchoolYear::active()?->start_year ?? AcademicYear::currentStartYear();
@endphp
<div class="academic-hierarchy-fields grid grid-cols-2 gap-4 col-span-2">
    <div class="form-group">
        <label class="form-label">School Year <span class="text-red-500">*</span></label>
        <select name="academic_year_start" class="form-control" required>
            <option value="{{ $activeStart }}" selected>{{ AcademicYear::label($activeStart) }}</option>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Semester <span class="text-red-500">*</span></label>
        <select name="semester" class="form-control" required>
            @foreach(config('academic.semesters', []) as $key => $label)
                <option value="{{ $key }}" {{ $semester === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    @include('partials.ite-subject-picker', ['pickerId' => $pickerId ?? 'hierarchySubjectPicker'])
    @if($mode === 'teaching-guide')
    <div class="form-group">
        <label class="form-label">Folder <span class="text-red-500">*</span></label>
        <select name="guide_type" class="form-control" required>
            <option value="teaching-guides" {{ in_array($guideType, ['teaching-guides', 'lesson'], true) ? 'selected' : '' }}>TG (Teaching Guide)</option>
            <option value="lab-manual" {{ $guideType === 'lab-manual' ? 'selected' : '' }}>LB (Laboratory Manual)</option>
        </select>
    </div>
    @endif
    <p class="col-span-2 text-xs text-gray-500 dark:text-gray-400 mb-0">
        <i class="fas fa-folder-tree mr-1"></i>
        Files are placed in the active school year under the selected semester (TG or LB).
    </p>
</div>
