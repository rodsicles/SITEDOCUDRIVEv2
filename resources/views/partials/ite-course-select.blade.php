{{-- Leaf-folder course picker (same UI as ite-subject-picker) --}}
@include('partials.ite-subject-picker', [
    'pickerId' => $pickerId ?? ($inputId ?? 'folderCoursePicker'),
    'required' => $required ?? true,
])
