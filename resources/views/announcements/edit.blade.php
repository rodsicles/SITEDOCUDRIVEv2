@extends('layouts.dashboard')

@section('title', 'Edit Announcement')

@section('page-title', 'Edit Announcement')
@section('page-subtitle', 'Update your announcement')

@section('sidebar')
    @if($sidebar['rolePrefix'] === 'dean')
        @include('partials.dean-sidebar')
    @elseif($sidebar['rolePrefix'] === 'coordinator')
        @include('partials.coordinator-sidebar')
    @endif
@endsection

@section('content')
    <div class="content-card announcement-edit-card">
        <div class="card-header announcements-page__header">
            <h3 class="card-title announcements-page__title">
                <i class="fas fa-edit mr-1.5"></i> Edit
            </h3>
            <a href="{{ route('announcements.index') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>

        <form action="{{ route('announcements.update', $announcement->announcement_id) }}" method="POST" class="announcement-composer is-open">
            @csrf
            @method('PUT')

            @if($errors->any())
            <div class="announcement-composer__errors">
                @foreach($errors->all() as $error)
                    <p class="m-0">{{ $error }}</p>
                @endforeach
            </div>
            @endif

            <div class="announcement-composer__main">
                <input type="text" name="title" class="announcement-composer__title" value="{{ old('title', $announcement->title) }}" placeholder="Announcement title" required maxlength="255">
                <textarea name="body" id="body" class="announcement-composer__body" rows="4" placeholder="Write a short update…" required maxlength="5000">{{ old('body', $announcement->body) }}</textarea>
            </div>

            <div class="announcement-composer__toolbar">
                <label class="announcement-composer__audience-wrap">
                    <span class="sr-only">Audience</span>
                    <select name="audience" class="announcement-composer__audience" required>
                        @foreach($audienceOptions as $value => $label)
                            <option value="{{ $value }}" @selected($selectedAudience === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-save mr-1"></i> Save
                </button>
            </div>

            <div class="announcement-composer__advanced">
                <div class="announcement-composer__advanced-row">
                    <label class="announcement-composer__field">
                        <span>Expires (optional)</span>
                        <input type="datetime-local"
                               name="expires_at"
                               class="form-control announcement-composer__control"
                               value="{{ old('expires_at', $announcement->expires_at ? $announcement->expires_at->format('Y-m-d\TH:i') : '') }}"
                               min="{{ now()->format('Y-m-d') }}T00:00"
                               max="{{ date('Y') . '-12-31T23:59' }}">
                    </label>
                    <label class="announcement-composer__pin">
                        <input type="hidden" name="is_pinned" value="0">
                        <input type="checkbox" name="is_pinned" value="1" {{ old('is_pinned', $announcement->is_pinned) ? 'checked' : '' }}>
                        <span><i class="fas fa-thumbtack text-[#028a0f]"></i> Pin to top</span>
                    </label>
                </div>
            </div>
        </form>
    </div>
@endsection
