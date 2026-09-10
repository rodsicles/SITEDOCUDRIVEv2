{{-- Inline feed composer: title + body + one audience; expire/pin collapsed --}}
@php
    $audienceOptions = \App\Http\Controllers\AnnouncementController::audienceOptions();
    $selectedAudience = old('audience', 'everyone');
    $showAdvanced = old('expires_at') || old('is_pinned');
    $openComposer = $errors->any() || request()->boolean('compose') || session('open_composer');
@endphp

<form id="announcementComposer"
      action="{{ route('announcements.store') }}"
      method="POST"
      class="announcement-composer {{ $openComposer ? 'is-open' : '' }}">
    @csrf

    @if($errors->any())
    <div class="announcement-composer__errors">
        @foreach($errors->all() as $error)
            <p class="m-0">{{ $error }}</p>
        @endforeach
    </div>
    @endif

    <div class="announcement-composer__main">
        <input type="text"
               name="title"
               id="composerTitle"
               class="announcement-composer__title"
               value="{{ old('title') }}"
               placeholder="Announcement title"
               required
               maxlength="255"
               autocomplete="off">

        <textarea name="body"
                  id="composerBody"
                  class="announcement-composer__body"
                  rows="3"
                  placeholder="Write a short update…"
                  required
                  maxlength="5000">{{ old('body') }}</textarea>
    </div>

    <div class="announcement-composer__toolbar">
        <label class="announcement-composer__audience-wrap">
            <span class="sr-only">Audience</span>
            <select name="audience" id="composerAudience" class="announcement-composer__audience" required>
                @foreach($audienceOptions as $value => $label)
                    <option value="{{ $value }}" @selected($selectedAudience === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>

        <button type="button"
                class="announcement-composer__more"
                id="composerMoreBtn"
                aria-expanded="{{ $showAdvanced ? 'true' : 'false' }}"
                aria-controls="composerAdvanced">
            <i class="fas fa-sliders-h" aria-hidden="true"></i> More options
        </button>

        <button type="submit" class="btn btn-primary btn-sm announcement-composer__submit">
            <i class="fas fa-paper-plane mr-1" aria-hidden="true"></i> Post
        </button>
    </div>

    <div id="composerAdvanced"
         class="announcement-composer__advanced {{ $showAdvanced ? '' : 'is-collapsed' }}">
        <div class="announcement-composer__advanced-row">
            <label class="announcement-composer__field">
                <span>Expires (optional)</span>
                <input type="datetime-local"
                       name="expires_at"
                       class="form-control announcement-composer__control"
                       value="{{ old('expires_at') }}"
                       min="{{ now()->format('Y-m-d') }}T00:00"
                       max="{{ date('Y') . '-12-31T23:59' }}">
            </label>
            <label class="announcement-composer__pin">
                <input type="hidden" name="is_pinned" value="0">
                <input type="checkbox" name="is_pinned" value="1" {{ old('is_pinned') ? 'checked' : '' }}>
                <span><i class="fas fa-thumbtack text-[#028a0f]" aria-hidden="true"></i> Pin to top</span>
            </label>
        </div>
        <p class="announcement-composer__hint">Defaults: everyone · no expiration · not pinned</p>
    </div>
</form>
