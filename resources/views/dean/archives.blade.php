@extends('layouts.dashboard')

@section('title', 'School Year Archives - Dean')
@section('page-title', 'School Year Archives')
@section('page-subtitle', 'Manage school year archiving')

@section('sidebar')
    @include('partials.dean-sidebar')
@endsection

@section('content')
    {{-- Active School Year --}}
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-calendar-alt mr-2"></i>Current School Year</h3>
            <span class="badge badge-success">Active</span>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">School Year</p>
                    <p class="text-lg font-bold text-gray-800 dark:text-white">{{ $activeSchoolYear->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Documents</p>
                    <p class="text-lg font-bold text-gray-800 dark:text-white">{{ $activeDocCount }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Teaching Guides <span class="text-xs">(approved)</span></p>
                    <p class="text-lg font-bold text-gray-800 dark:text-white">{{ $activeTgCount }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Exam Questionnaires <span class="text-xs">(approved)</span></p>
                    <p class="text-lg font-bold text-gray-800 dark:text-white">{{ $activeEqCount }}</p>
                </div>
            </div>

            <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-4">
                <button type="button" class="btn btn-danger border-0" onclick="document.getElementById('archiveModal').classList.remove('hidden')">
                    <i class="fas fa-archive"></i> Archive This School Year
                </button>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    Documents for this year are archived in full. Only <strong>approved</strong> teaching guides and exam questionnaires are kept in the archive; pending and rejected submissions stay in the active year for review or faculty cleanup.
                </p>
                @if(($pendingTgCount + $pendingEqCount + $rejectedTgCount + $rejectedEqCount) > 0)
                <p class="text-xs text-amber-700 dark:text-amber-300 mt-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    Not archived with this year:
                    @if($pendingTgCount + $pendingEqCount > 0)
                        {{ $pendingTgCount + $pendingEqCount }} pending
                    @endif
                    @if($pendingTgCount + $pendingEqCount > 0 && $rejectedTgCount + $rejectedEqCount > 0)
                        ,
                    @endif
                    @if($rejectedTgCount + $rejectedEqCount > 0)
                        {{ $rejectedTgCount + $rejectedEqCount }} rejected
                    @endif
                    (carried forward to the new school year).
                </p>
                @endif
            </div>
        </div>
    </div>

    @if($allowArchiveHardDelete && $archivedYears->isNotEmpty())
    <div class="content-card border-2 border-red-200 dark:border-red-900/50">
        <div class="card-header bg-red-50 dark:bg-red-900/20">
            <h3 class="card-title text-red-800 dark:text-red-200">
                <i class="fas fa-exclamation-triangle mr-2"></i>Dry-run cleanup — permanent archive delete
            </h3>
        </div>
        <div class="p-4 text-sm text-gray-600 dark:text-gray-300">
            <p>Permanently removes an <strong>archived</strong> school year bucket and all documents, teaching guides, exam questionnaires, exam records, and semester folders tagged to that year. Storage files are deleted. This cannot be undone.</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Does not remove active-year data, notifications, or dashboard logs. Set <code class="text-xs">ALLOW_ARCHIVE_HARD_DELETE=false</code> when dry runs end.</p>
        </div>
    </div>
    @endif

    {{-- Archived School Years --}}
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-box-archive mr-2"></i>Archived School Years</h3>
            <span class="badge badge-info">{{ $archivedYears->count() }} Archives</span>
        </div>
        @if($archivedYears->isEmpty())
            <div class="p-6 text-center text-gray-500 dark:text-gray-400">
                <i class="fas fa-inbox text-4xl mb-3"></i>
                <p>No archived school years yet. When you archive the current school year, it will appear here.</p>
            </div>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>School Year</th>
                        <th>Archived On</th>
                        <th>Archived By</th>
                        @if($allowArchiveHardDelete)
                        <th>Records</th>
                        @endif
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($archivedYears as $year)
                    <tr>
                        <td><strong>{{ $year->name }}</strong></td>
                        <td>{{ $year->archived_at->format('M d, Y h:i A') }}</td>
                        <td>{{ optional($year->archivedByUser)->employee->full_name ?? optional($year->archivedByUser)->username ?? 'System' }}</td>
                        @if($allowArchiveHardDelete)
                        <td class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $year->documents_count }} doc · {{ $year->teaching_guides_count }} TG · {{ $year->exam_questionnaires_count }} EQ · {{ $year->folders_count }} folders
                        </td>
                        @endif
                        <td class="flex flex-wrap gap-2 relative z-10 whitespace-normal">
                            <a href="{{ route('dean.archives.show', $year->id) }}" class="btn btn-sm btn-primary border-0">
                                <i class="fas fa-eye"></i> Browse
                            </a>
                            <button type="button"
                                    class="btn btn-sm btn-success border-0 js-archive-restore-open"
                                    data-restore-url="{{ route('dean.archives.restore', $year) }}"
                                    data-restore-name="{{ $year->name }}">
                                <i class="fas fa-undo"></i> Restore as Active
                            </button>
                            @if($allowArchiveHardDelete)
                            <button type="button"
                                    class="btn btn-sm btn-danger border-0 js-archive-delete-open"
                                    data-delete-url="{{ route('dean.archives.destroy', $year) }}"
                                    data-delete-name="{{ $year->name }}">
                                <i class="fas fa-trash-alt"></i> Delete permanently
                            </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Archive Modal --}}
    <div id="archiveModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-[#1e1e1e] rounded-lg shadow-xl max-w-lg w-full">
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">
                    <i class="fas fa-archive text-red-500 mr-2"></i>Archive School Year
                </h3>

                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded p-3 mb-4">
                    <p class="text-sm text-yellow-800 dark:text-yellow-200">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <strong>Warning:</strong> This action archives all documents from the current school year and only <strong>approved</strong> teaching guides and exam questionnaires. Pending and rejected submissions are <strong>not</strong> archived—they remain active for the new school year. User-created folders are archived with the year. The system will start fresh with empty default folders.
                    </p>
                </div>

                {{-- In-modal validation errors --}}
                @if($errors->any())
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded p-3 mb-4">
                    <ul class="text-sm text-red-700 dark:text-red-300 list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form action="{{ route('dean.archives.archive') }}" method="POST" id="archiveForm" data-request-guard>
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="text-sm font-semibold text-gray-600 dark:text-gray-300 block mb-1">Archive Name</label>
                            <input type="text" name="archive_name" value="{{ old('archive_name', $activeSchoolYear->name) }}" class="form-control" required maxlength="50">
                            <p class="text-xs text-gray-400 mt-1">Name for the archived school year</p>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gray-600 dark:text-gray-300 block mb-1">New School Year Name</label>
                            <input type="text" name="new_name" value="{{ old('new_name', 'S.Y. ' . $suggestedStartYear . '-' . ($suggestedStartYear + 1)) }}" class="form-control" required maxlength="50">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gray-600 dark:text-gray-300 block mb-1">New School Year Start</label>
                            <input type="number" name="new_start_year" value="{{ old('new_start_year', $suggestedStartYear) }}" class="form-control" required min="2020" max="2099">
                            <p class="text-xs text-gray-400 mt-1">The start year of the new school year (e.g. {{ $suggestedStartYear }} for {{ $suggestedStartYear }}-{{ $suggestedStartYear + 1 }})</p>
                        </div>
                    </div>

                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-4">
                        <i class="fas fa-info-circle mr-1"></i>This may take a few seconds. Please click only once and do not refresh the page.
                    </p>

                    <div class="flex justify-end gap-3 mt-4">
                        <button type="button" id="archiveCancelBtn" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300" onclick="document.getElementById('archiveModal').classList.add('hidden')">
                            Cancel
                        </button>
                        <button type="submit" id="archiveSubmitBtn" class="btn btn-danger border-0" onclick="return confirm('Are you absolutely sure? This cannot be undone.')">
                            <i class="fas fa-archive"></i> Confirm Archive
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('archiveModal').classList.remove('hidden');
        });
    </script>
    @endif

    @if($allowArchiveHardDelete)
    <div id="archiveDeleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[60] flex items-center justify-center p-4">
        <div class="bg-white dark:bg-[#1e1e1e] rounded-lg shadow-xl max-w-lg w-full">
            <div class="p-6">
                <h3 class="text-lg font-bold text-red-600 dark:text-red-400 mb-2">
                    <i class="fas fa-trash-alt mr-2"></i>Permanently delete archived school year
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                    This wipes <strong id="archiveDeleteYearLabel"></strong> and every document, teaching guide, exam questionnaire, and folder tagged to that archive. Cannot be undone.
                </p>

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

                <form action="" method="POST" id="archiveDeleteForm" data-request-guard>
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="form-label" for="confirm_name">Type the school year name exactly</label>
                            <input type="text" name="confirm_name" id="confirm_name" class="form-control" value="{{ old('confirm_name') }}" autocomplete="off" required maxlength="50">
                        </div>
                        <div>
                            <label class="form-label" for="confirm_phrase">Type DELETE PERMANENTLY</label>
                            <input type="text" name="confirm_phrase" id="confirm_phrase" class="form-control" placeholder="DELETE PERMANENTLY" autocomplete="off" required maxlength="50">
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300" onclick="closeArchiveDeleteModal()">Cancel</button>
                        <button type="button" class="btn btn-danger border-0" onclick="submitArchiveDelete()">
                            <i class="fas fa-trash-alt"></i> Delete permanently
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    (function () {
        var deleteModal = document.getElementById('archiveDeleteModal');
        if (!deleteModal) {
            return;
        }

        var deleteForm = document.getElementById('archiveDeleteForm');
        var deleteLabel = document.getElementById('archiveDeleteYearLabel');
        var deleteExpectedName = '';

        function openArchiveDeleteModal(url, name) {
            deleteExpectedName = name || '';
            if (deleteLabel) {
                deleteLabel.textContent = deleteExpectedName;
            }
            if (deleteForm) {
                deleteForm.action = url || '';
            }
            deleteModal.classList.remove('hidden');
        }

        function closeArchiveDeleteModal() {
            deleteModal.classList.add('hidden');
        }

        window.closeArchiveDeleteModal = closeArchiveDeleteModal;

        document.addEventListener('click', function (event) {
            var openBtn = event.target.closest('.js-archive-delete-open');
            if (openBtn) {
                event.preventDefault();
                event.stopPropagation();
                openArchiveDeleteModal(
                    openBtn.getAttribute('data-delete-url'),
                    openBtn.getAttribute('data-delete-name')
                );
            }
        });

        window.submitArchiveDelete = function () {
            if (!deleteForm) {
                return;
            }

            var nameInput = deleteForm.querySelector('[name="confirm_name"]');
            var phraseInput = deleteForm.querySelector('[name="confirm_phrase"]');

            if (nameInput && nameInput.value.trim() !== deleteExpectedName) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ title: 'Name mismatch', text: 'Type the school year name exactly as shown.', icon: 'error', confirmButtonColor: '#028a0f', customClass: { popup: 'swal-flat' } });
                } else {
                    alert('School year name does not match.');
                }
                return;
            }

            if (phraseInput && phraseInput.value.trim() !== 'DELETE PERMANENTLY') {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ title: 'Confirmation required', text: 'Type DELETE PERMANENTLY in all caps.', icon: 'error', confirmButtonColor: '#028a0f', customClass: { popup: 'swal-flat' } });
                } else {
                    alert('Type DELETE PERMANENTLY to confirm.');
                }
                return;
            }

            var message = 'This will permanently delete the entire archived school year and all linked files. This cannot be undone.';
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Delete archived school year?',
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, delete permanently',
                    customClass: { popup: 'swal-flat' }
                }).then(function (result) {
                    if (result.isConfirmed) {
                        deleteForm.submit();
                    }
                });
            } else if (confirm(message)) {
                deleteForm.submit();
            }
        };

        @if(old('confirm_phrase') && str_contains(strtoupper((string) old('confirm_phrase')), 'DELETE') && $errors->any())
        document.addEventListener('DOMContentLoaded', function () {
            openArchiveDeleteModal('', @json(old('confirm_name')));
            deleteExpectedName = @json(old('confirm_name'));
        });
        @endif
    })();
    </script>
    @endif

    @if($archivedYears->isNotEmpty())
        @include('partials.archive-restore-modal')
    @endif
@endsection

@push('scripts')
<script>
(function () {
    var restoreModal = document.getElementById('archiveRestoreModal');
    if (!restoreModal) {
        return;
    }

    var restoreForm = document.getElementById('archiveRestoreForm');
    var restoreLabel = document.getElementById('archiveRestoreYearLabel');
    var restoreExpectedName = '';

    function openArchiveRestoreModal(url, name) {
        restoreExpectedName = name || '';
        if (restoreLabel) {
            restoreLabel.textContent = restoreExpectedName;
        }
        if (restoreForm) {
            restoreForm.action = url || '';
        }
        restoreModal.classList.remove('hidden');
    }

    function closeArchiveRestoreModal() {
        restoreModal.classList.add('hidden');
    }

    document.addEventListener('click', function (event) {
        var openBtn = event.target.closest('.js-archive-restore-open');
        if (openBtn) {
            event.preventDefault();
            event.stopPropagation();
            openArchiveRestoreModal(
                openBtn.getAttribute('data-restore-url'),
                openBtn.getAttribute('data-restore-name')
            );
            return;
        }

        if (event.target === restoreModal) {
            closeArchiveRestoreModal();
        }
    });

    var restoreCancelBtn = document.getElementById('archiveRestoreCancelBtn');
    if (restoreCancelBtn) {
        restoreCancelBtn.addEventListener('click', closeArchiveRestoreModal);
    }

    var restoreSubmitBtn = document.getElementById('archiveRestoreSubmitBtn');
    if (restoreSubmitBtn) {
        restoreSubmitBtn.addEventListener('click', function () {
            if (!restoreForm) {
                return;
            }

            var nameInput = restoreForm.querySelector('[name="confirm_name"]');
            var phraseInput = restoreForm.querySelector('[name="confirm_phrase"]');

            if (nameInput && nameInput.value.trim() !== restoreExpectedName) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ title: 'Name mismatch', text: 'Type the school year name exactly as shown.', icon: 'error', confirmButtonColor: '#028a0f', customClass: { popup: 'swal-flat' } });
                } else {
                    alert('School year name does not match.');
                }
                return;
            }

            if (phraseInput && phraseInput.value.trim() !== 'RESTORE AS ACTIVE') {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ title: 'Confirmation required', text: 'Type RESTORE AS ACTIVE in all caps.', icon: 'error', confirmButtonColor: '#028a0f', customClass: { popup: 'swal-flat' } });
                } else {
                    alert('Type RESTORE AS ACTIVE to confirm.');
                }
                return;
            }

            var message = 'Restore this school year as active? The current active year and its data will be removed. Analytics will sync to the restored year.';
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Restore school year?',
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#028a0f',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, restore as active',
                    customClass: { popup: 'swal-flat' }
                }).then(function (result) {
                    if (result.isConfirmed) {
                        restoreForm.submit();
                    }
                });
            } else if (confirm(message)) {
                restoreForm.submit();
            }
        });
    }

    @if(old('confirm_phrase') && str_contains(strtoupper((string) old('confirm_phrase')), 'RESTORE') && $errors->any())
    document.addEventListener('DOMContentLoaded', function () {
        openArchiveRestoreModal('', @json(old('confirm_name')));
        restoreExpectedName = @json(old('confirm_name'));
    });
    @endif
})();
</script>
@endpush