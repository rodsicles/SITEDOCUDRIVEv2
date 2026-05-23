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

function confirmSubmissionApprove(formId) {
    var form = document.getElementById(formId);
    if (!form) return;

    var submit = function () {
        closeSubmissionPopovers();
        form.submit();
    };

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Approve submission?',
            text: 'The faculty member will be notified that this file was approved.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, approve',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#028a0f',
            cancelButtonColor: '#6b7280',
            customClass: { popup: 'swal-flat' }
        }).then(function (result) {
            if (result.isConfirmed) submit();
        });
        return;
    }

    if (confirm('Approve this submission?')) submit();
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
            confirmSubmissionApprove(btn.dataset.approveForm);
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
