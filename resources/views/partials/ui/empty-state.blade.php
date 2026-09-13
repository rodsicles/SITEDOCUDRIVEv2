@php
    $title = $title ?? 'Nothing to show';
    $text = $text ?? 'This list is empty.';
    $actionUrl = $actionUrl ?? null;
    $actionLabel = $actionLabel ?? null;
@endphp
<div class="ui-empty">
    <p class="ui-empty__title">{{ $title }}</p>
    <p class="ui-empty__text">{{ $text }}</p>
    @if($actionUrl && $actionLabel)
        <a href="{{ $actionUrl }}" class="btn btn-primary text-sm mt-3">{{ $actionLabel }}</a>
    @endif
</div>
