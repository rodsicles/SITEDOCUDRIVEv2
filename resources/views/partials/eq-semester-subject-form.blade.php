<form action="{{ route('documents.open-eq-subject') }}" method="POST" class="semester-subject-open-form mb-4 p-4 border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
    @csrf
    <input type="hidden" name="folder_id" value="{{ $currentFolder->folder_id }}">
    <input type="hidden" name="tab" value="{{ $tab }}">
    @include('partials.ite-subject-picker', [
        'pickerId' => 'eqSemesterSubjectPicker',
        'subjects' => \App\Support\IteSubjects::labelsForUser(auth()->user()),
        'required' => true,
        'compact' => true,
        'inlineSubmit' => true,
    ])
</form>
