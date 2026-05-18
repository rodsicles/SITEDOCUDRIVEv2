{{-- ITE course dropdown for TG / LB / TOS / TOQ folder uploads --}}
@php
    $inputId = $inputId ?? 'iteCourseSelect';
    $fieldName = $fieldName ?? 'subject';
    $subjects = \App\Support\IteSubjects::labels();
    $selected = old($fieldName);
@endphp
<div class="form-group md:col-span-2">
    <label class="form-label" for="{{ $inputId }}">ITE Course <span class="text-red-500">*</span></label>
    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Select the subject for this upload. The document will be labeled with the course code.</p>
    <select name="{{ $fieldName }}" id="{{ $inputId }}" class="form-control" required>
        <option value="">-- Select ITE Course --</option>
        @foreach($subjects as $label)
            <option value="{{ $label }}" {{ $selected === $label ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    @error($fieldName)<span class="text-red-500 text-xs block mt-1">{{ $message }}</span>@enderror
    @error('document_title')<span class="text-red-500 text-xs block mt-1">{{ $message }}</span>@enderror
</div>
