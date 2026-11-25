{{--
  Shared "Leave a revision note" modal for Teaching Guides / Exam Questionnaires.
  Open it with any button carrying data-open-note-modal, data-note-url, data-note-title, data-note-version.
--}}
@php $noteFailed = $errors->has('body') || $errors->has('location'); @endphp
<dialog id="submissionNoteModal" class="ui-dialog ui-dialog--note" aria-labelledby="submissionNoteTitle" aria-describedby="submissionNoteIntro">
    <form method="POST" action="{{ old('_note_url') }}" id="submissionNoteForm" class="ui-dialog__form" data-request-guard>
        @csrf
        <input type="hidden" name="_note_url" id="submissionNoteUrl" value="{{ old('_note_url') }}">
        <header class="ui-dialog__head submission-note-modal__head">
            <h2 id="submissionNoteTitle" class="ui-dialog__title"><i class="fas fa-comment-dots" aria-hidden="true"></i> Leave a revision note</h2>
            <button type="button" class="ui-dialog__close" data-close-note-modal aria-label="Close">&times;</button>
        </header>
        <div class="ui-dialog__body">
            <p id="submissionNoteIntro" class="submission-note-modal__target">
                For <strong id="submissionNoteTarget">{{ old('_note_title', 'this submission') }}</strong>
                <span id="submissionNoteVersion" class="badge badge-info">v{{ old('_note_version', 1) }}</span>
            </p>
            <input type="hidden" name="_note_title" id="submissionNoteTitleInput" value="{{ old('_note_title') }}">
            <input type="hidden" name="_note_version" id="submissionNoteVersionInput" value="{{ old('_note_version') }}">
            @if($noteFailed)
                <p class="ui-dialog__error" role="alert">{{ $errors->first('body') ?: $errors->first('location') }}</p>
            @endif
            <div class="form-group">
                <label class="form-label" for="submissionNoteLocation">Which part? <span class="text-gray-500">(optional)</span></label>
                <input id="submissionNoteLocation" name="location" class="form-control" maxlength="150" value="{{ old('location') }}" placeholder="e.g. Part II, item 5 · Page 3 · leave blank for a general note">
            </div>
            <div class="form-group mb-0">
                <label class="form-label" for="submissionNoteBody">What needs to change? <span class="text-red-500">*</span></label>
                <textarea id="submissionNoteBody" name="body" class="form-control" rows="4" minlength="3" maxlength="2000" required placeholder="e.g. Wrong answer key — item 5 should be C.">{{ old('body') }}</textarea>
                <small class="text-xs text-gray-500">The faculty member is notified right away and will mark the note as addressed after revising.</small>
            </div>
        </div>
        <footer class="ui-dialog__foot">
            <button type="button" class="btn btn-secondary" data-close-note-modal>Cancel</button>
            <button type="submit" class="btn btn-primary submission-note-modal__submit"><i class="fas fa-paper-plane" aria-hidden="true"></i> Send note</button>
        </footer>
    </form>
</dialog>
<script>
(function () {
    var modal = document.getElementById('submissionNoteModal');
    if (!modal || modal.dataset.ready) return;
    modal.dataset.ready = '1';
    var form = document.getElementById('submissionNoteForm');
    function open(button) {
        form.action = button.dataset.noteUrl;
        document.getElementById('submissionNoteUrl').value = button.dataset.noteUrl;
        document.getElementById('submissionNoteTarget').textContent = button.dataset.noteTitle || 'this submission';
        document.getElementById('submissionNoteTitleInput').value = button.dataset.noteTitle || '';
        document.getElementById('submissionNoteVersion').textContent = 'v' + (button.dataset.noteVersion || 1);
        document.getElementById('submissionNoteVersionInput').value = button.dataset.noteVersion || 1;
        if (!modal.open) modal.showModal();
        document.getElementById('submissionNoteLocation').focus();
    }
    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-open-note-modal]');
        if (trigger) { event.preventDefault(); open(trigger); return; }
        if (event.target.closest('[data-close-note-modal]') || event.target === modal) modal.close();
    });
    @if($noteFailed && old('_note_url'))
    modal.showModal();
    @endif
})();
</script>
