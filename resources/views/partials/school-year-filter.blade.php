{{--
    School year filter for major modules.
    @param string $paramName Query param name (default: academic_year)
    @param string|null $selected Currently selected start year
--}}
@php
    use App\Support\AcademicYear;
    $paramName = $paramName ?? 'academic_year';
    $selected = $selected ?? request($paramName, (string) AcademicYear::currentStartYear());
    $preserve = $preserveQuery ?? request()->except([$paramName, 'page']);
@endphp
<div class="school-year-filter flex items-center gap-2 flex-wrap">
    <label class="text-xs font-semibold text-gray-600 dark:text-gray-300 whitespace-nowrap">
        <i class="fas fa-calendar-alt mr-1 text-[#028a0f]"></i> School Year
    </label>
    <select name="{{ $paramName }}" class="form-control text-sm" style="min-width: 180px;"
            onchange="if(this.form) this.form.submit();">
        <option value="">All Years (Archive)</option>
        @foreach(AcademicYear::options() as $startYear => $label)
            @php $isArchive = AcademicYear::isArchived((int) $startYear); @endphp
            <option value="{{ $startYear }}" {{ (string) $selected === (string) $startYear ? 'selected' : '' }}>
                {{ $label }}{{ $isArchive ? ' — Archive' : '' }}
            </option>
        @endforeach
    </select>
    @foreach($preserve as $key => $value)
        @if(is_array($value))
            @foreach($value as $v)
                <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
            @endforeach
        @elseif($value !== null && $value !== '')
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach
</div>
