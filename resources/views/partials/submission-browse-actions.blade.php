@props([
    'viewUrl',
    'downloadUrl',
    'viewLabel' => 'View',
    'downloadLabel' => 'Download',
    'deleteUrl' => null,
    'deleteConfirm' => 'Delete this item?',
])
<div class="submission-browse-actions">
    @include('partials.archive-row-actions', [
        'viewUrl' => $viewUrl,
        'downloadUrl' => $downloadUrl,
        'viewLabel' => $viewLabel,
        'downloadLabel' => $downloadLabel,
    ])
    @if($deleteUrl)
        <form action="{{ $deleteUrl }}" method="POST" class="submission-browse-actions__delete" data-request-guard onsubmit="return confirm(@js($deleteConfirm))">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="btn btn-sm btn-danger border-0 submission-browse-actions__btn"
                    title="Delete"
                    aria-label="Delete">
                <i class="fas fa-trash" aria-hidden="true"></i>
            </button>
        </form>
    @endif
</div>
