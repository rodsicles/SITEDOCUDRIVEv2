{{--
    Structured upload fields: School Year → Semester → Subject → Assessment → Guide Type → Version
    @param string $mode 'teaching-guide' | 'exam-questionnaire'
--}}
@php
    use App\Support\AcademicYear;
    $mode = $mode ?? 'teaching-guide';
    $startYear = (int) old('academic_year_start', AcademicYear::currentStartYear());
    $semester = old('semester', AcademicYear::currentSemester());
    $assessment = old('assessment_period', 'prelims');
    $guideType = old('guide_type', 'teaching-guides');
    $version = old('version_type', 'final');
@endphp
<div class="academic-hierarchy-fields grid grid-cols-2 gap-4 col-span-2">
    <div class="form-group">
        <label class="form-label">School Year <span class="text-red-500">*</span></label>
        <select name="academic_year_start" class="form-control" required>
            @foreach(AcademicYear::options() as $year => $label)
                <option value="{{ $year }}" {{ (int) $startYear === (int) $year ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
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
    <div class="form-group">
        <label class="form-label">Assessment Period <span class="text-red-500">*</span></label>
        <select name="assessment_period" class="form-control" required>
            @foreach(config('academic.assessment_periods', []) as $slug => $label)
                <option value="{{ $slug }}" {{ $assessment === $slug ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    @if($mode === 'teaching-guide')
    <div class="form-group">
        <label class="form-label">Guide Type <span class="text-red-500">*</span></label>
        <select name="guide_type" class="form-control" required>
            @foreach(config('academic.guide_types', []) as $slug => $label)
                <option value="{{ $slug }}" {{ $guideType === $slug ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Version <span class="text-red-500">*</span></label>
        <select name="version_type" class="form-control" required>
            @foreach(config('academic.version_types', []) as $slug => $label)
                <option value="{{ $slug }}" {{ $version === $slug ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    @endif
    <input type="hidden" name="folder_id" id="hierarchyFolderId" value="{{ old('folder_id') }}">
    <p class="col-span-2 text-xs text-gray-500 dark:text-gray-400 mb-0">
        <i class="fas fa-folder-tree mr-1"></i>
        Folders are created automatically when you upload (School Year → Semester → Subject → Assessment → Type).
    </p>
</div>
