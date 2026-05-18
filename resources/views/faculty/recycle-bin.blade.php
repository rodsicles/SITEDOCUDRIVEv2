@extends('layouts.dashboard')

@section('title', 'Recycle Bin - Faculty')

@section('page-title', 'Recycle Bin')
@section('page-subtitle', 'Restore files you moved here from Documents')

@section('sidebar')
    @include('partials.faculty-sidebar')
@endsection

@section('content')
    @include('partials.recycle-bin-table', ['routePrefix' => 'faculty', 'canForceDelete' => false])
@endsection

@push('scripts')
<script>
function confirmPermanentDelete(btn) {
    const form = btn.closest('form');
    if (!form) return;
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Delete permanently?',
            text: 'This file will be removed from storage and cannot be recovered.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete forever',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            customClass: { popup: 'swal-flat' }
        }).then((result) => {
            if (result.isConfirmed) form.submit();
        });
        return;
    }
    if (confirm('Delete this file permanently? This cannot be undone.')) {
        form.submit();
    }
}
</script>
@endpush
