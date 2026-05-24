    {{-- Restore Modal --}}
    <div id="archiveRestoreModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[60] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="archiveRestoreTitle">
        <div class="bg-white dark:bg-[#1e1e1e] rounded-lg shadow-xl max-w-lg w-full relative z-[61]">
            <div class="p-6">
                <h3 id="archiveRestoreTitle" class="text-lg font-bold text-[#028a0f] dark:text-green-400 mb-2">
                    <i class="fas fa-undo mr-2"></i>Restore school year as active
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                    This will make <strong id="archiveRestoreYearLabel"></strong> the current school year again.
                    The active school year (<strong>{{ $activeSchoolYear->name }}</strong>) and its data will be permanently removed.
                    Faculty performance, Data Analytics, and submission insights will sync to the restored year.
                </p>

                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded p-3 mb-4">
                    <p class="text-sm text-yellow-800 dark:text-yellow-200">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Pending and rejected items carried forward from the archive test will be re-tagged to this school year.
                    </p>
                </div>

                @if($errors->has('confirm_name') || $errors->has('confirm_phrase') || $errors->has('error'))
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded p-3 mb-4">
                    <ul class="text-sm text-red-700 dark:text-red-300 list-disc list-inside">
                        @foreach(['confirm_name', 'confirm_phrase', 'error'] as $field)
                            @error($field)
                                <li>{{ $message }}</li>
                            @enderror
                        @endforeach
                    </ul>
                </div>
                @endif

                <form action="" method="POST" id="archiveRestoreForm">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="form-label" for="restore_confirm_name">Type the school year name exactly</label>
                            <input type="text" name="confirm_name" id="restore_confirm_name" class="form-control" value="{{ old('confirm_name') }}" autocomplete="off" required maxlength="50">
                        </div>
                        <div>
                            <label class="form-label" for="restore_confirm_phrase">Type RESTORE AS ACTIVE</label>
                            <input type="text" name="confirm_phrase" id="restore_confirm_phrase" class="form-control" placeholder="RESTORE AS ACTIVE" autocomplete="off" required maxlength="50">
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300" id="archiveRestoreCancelBtn">Cancel</button>
                        <button type="button" class="btn btn-success border-0" id="archiveRestoreSubmitBtn">
                            <i class="fas fa-undo"></i> Restore as Active
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
