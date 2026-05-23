@props([
    'viewUrl',
    'downloadUrl',
    'viewLabel' => 'View',
    'downloadLabel' => 'Download',
])
<div class="archive-row-actions">
    <a href="{{ $viewUrl }}"
       class="btn btn-sm btn-success border-0 archive-row-actions__btn"
       target="_blank"
       rel="noopener noreferrer"
       title="{{ $viewLabel }}"
       aria-label="{{ $viewLabel }}">
        <i class="fas fa-eye" aria-hidden="true"></i>
    </a>
    <a href="{{ $downloadUrl }}"
       class="btn btn-sm btn-success border-0 archive-row-actions__btn"
       title="{{ $downloadLabel }}"
       aria-label="{{ $downloadLabel }}">
        <i class="fas fa-download" aria-hidden="true"></i>
    </a>
</div>
