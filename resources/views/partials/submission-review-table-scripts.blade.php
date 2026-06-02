@once
@push('scripts')
<script>
function closeSubmissionPopovers() {
    document.querySelectorAll('.submission-review-popover').forEach(function (popover) {
        popover.classList.remove('is-open');
        popover.setAttribute('hidden', '');
        var key = popover.dataset.popoverKey;
        var btn = document.querySelector('.submission-actions-btn[data-popover-key="' + key + '"]');
        if (btn) btn.setAttribute('aria-expanded', 'false');
    });
}

function escapeSubmissionHtml(str) {
    var div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}

function confirmSubmissionApprove(formId, submissionTitle) {
    var form = document.getElementById(formId);
    if (!form) return;

    var label = (submissionTitle || '').trim() || 'Untitled submission';

    var submit = function () {
        closeSubmissionPopovers();
        form.submit();
    };

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Approve this submission?',
            html: '<p class="swal-approval-file" title="' + escapeSubmissionHtml(label) + '">'
                + '&ldquo;' + escapeSubmissionHtml(label) + '&rdquo;</p>'
                + '<p class="swal-approval-note">The faculty member will be notified that this file was approved.</p>',
            icon: false,
            showCancelButton: true,
            confirmButtonText: 'Approve',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#028a0f',
            cancelButtonColor: '#6b7280',
            width: '22rem',
            padding: '1.25rem 1.35rem 1rem',
            buttonsStyling: true,
            reverseButtons: true,
            focusCancel: false,
            customClass: {
                popup: 'swal-flat swal-approval',
                title: 'swal-approval-heading',
                htmlContainer: 'swal-approval-body',
                confirmButton: 'swal-approval-confirm',
                cancelButton: 'swal-approval-cancel',
                actions: 'swal-approval-actions',
            },
        }).then(function (result) {
            if (result.isConfirmed) submit();
        });
        return;
    }

    if (confirm('Approve “' + label + '”?')) submit();
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.submission-actions-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var key = btn.dataset.popoverKey;
            var popover = document.getElementById('submission-popover-' + key);
            if (!popover) return;
            var isOpen = popover.classList.contains('is-open');
            closeSubmissionPopovers();
            if (!isOpen) {
                popover.classList.add('is-open');
                popover.removeAttribute('hidden');
                btn.setAttribute('aria-expanded', 'true');
            }
        });
    });

    document.querySelectorAll('.submission-approve-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            confirmSubmissionApprove(btn.dataset.approveForm, btn.dataset.submissionTitle);
        });
    });

    document.addEventListener('click', function () {
        closeSubmissionPopovers();
    });

    document.querySelectorAll('.submission-review-popover').forEach(function (popover) {
        popover.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeSubmissionPopovers();
    });
});
</script>
@endpush
@endonce
