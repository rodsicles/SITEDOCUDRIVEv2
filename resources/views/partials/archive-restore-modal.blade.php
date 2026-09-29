    {{-- Restore dialog --}}
    <dialog id="archiveRestoreModal" class="ui-dialog" aria-labelledby="archiveRestoreTitle">
        <form action="" method="POST" id="archiveRestoreForm" class="ui-dialog__form" data-request-guard data-typed-confirm="RESTORE AS ACTIVE">
            @csrf
            <header class="ui-dialog__head">
                <div>
                    <h2 id="archiveRestoreTitle" class="ui-dialog__title"><i class="fas fa-undo" aria-hidden="true"></i> Restore school year as active</h2>
                    <p class="ui-dialog__subtitle">Make <strong data-year-label></strong> the current school year again.</p>
                </div>
                <button type="button" class="ui-dialog__close" data-dialog-close aria-label="Close">&times;</button>
            </header>

            <div class="ui-dialog__body">
                <div class="ui-callout">
                    <strong><i class="fas fa-exclamation-triangle mr-1" aria-hidden="true"></i> Before you restore</strong>
                    <ul>
                        <li>The active school year (<strong>{{ $activeSchoolYear->name }}</strong>) and its data will be permanently removed.</li>
                        <li>Faculty performance, Data Analytics, and submission insights sync to the restored year.</li>
                        <li>Pending and rejected items carried forward from the archive are re-tagged to this school year.</li>
                    </ul>
                </div>

                @if($errors->has('confirm_name') || $errors->has('confirm_phrase') || $errors->has('error'))
                <div class="ui-dialog__error" role="alert">
                    <ul>
                        @foreach(['confirm_name', 'confirm_phrase', 'error'] as $field)
                            @error($field)<li>{{ $message }}</li>@enderror
                        @endforeach
                    </ul>
                </div>
                @endif
                <p class="ui-dialog__error" data-inline-error role="alert" hidden></p>

                <div class="ui-dialog__field">
                    <label for="restore_confirm_name">Type the school year name exactly</label>
                    <input type="text" name="confirm_name" id="restore_confirm_name" class="form-control" value="{{ old('confirm_name') }}" autocomplete="off" required maxlength="50">
                </div>
                <div class="ui-dialog__field">
                    <label for="restore_confirm_phrase">Type RESTORE AS ACTIVE</label>
                    <input type="text" name="confirm_phrase" id="restore_confirm_phrase" class="form-control" placeholder="RESTORE AS ACTIVE" autocomplete="off" required maxlength="50">
                </div>
            </div>

            <footer class="ui-dialog__foot">
                <button type="button" class="btn btn-secondary" data-dialog-close>Cancel</button>
                <button type="submit" class="btn btn-success border-0">
                    <i class="fas fa-undo" aria-hidden="true"></i> Restore as active
                </button>
            </footer>
        </form>
    </dialog>
