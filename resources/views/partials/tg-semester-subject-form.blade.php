<form action="{{ route('documents.open-tg-subject') }}" method="POST" class="mb-4 p-4 border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
    @csrf
    <input type="hidden" name="folder_id" value="{{ $currentFolder->folder_id }}">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
        <div class="md:col-span-2">
            @include('partials.ite-subject-picker', [
                'pickerId' => 'tgSemesterSubjectPicker',
                'subjects' => \App\Support\IteSubjects::labelsForUser(auth()->user()),
                'required' => true,
            ])
        </div>
        <div>
            <button type="submit" class="btn btn-primary w-full">
                <i class="fas fa-folder-open mr-1"></i> Open subject folder
            </button>
        </div>
    </div>
</form>
