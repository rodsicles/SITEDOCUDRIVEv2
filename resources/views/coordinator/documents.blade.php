@extends('layouts.dashboard')

@section('title', 'Documents - Coordinator')

@section('page-title', 'Documents')
@section('page-subtitle', 'View all uploaded documents')

@section('sidebar')
    @include('partials.coordinator-sidebar')
@endsection

@section('content')
    @include('partials.folder-tree')

    @php
        $hierarchy = app(\App\Services\AcademicHierarchyService::class);
        $hideDocumentsList = isset($currentFolder)
            && $currentFolder
            && (
                $hierarchy->isSemesterTypeLeafFolder($currentFolder)
                || $hierarchy->isTgSemesterFolder($currentFolder)
                || $hierarchy->isTgSubjectFolder($currentFolder)
                || $hierarchy->isEqSemesterFolder($currentFolder)
                || $hierarchy->isEqSubjectFolder($currentFolder)
                || $hierarchy->isEqAssessmentFolder($currentFolder)
            );
    @endphp

    @if(!$hideDocumentsList)
    <div class="content-card">
        @include('partials.documents-filter-panel', [
            'documentsRoute' => 'coordinator.documents',
        ])
        @include('partials.documents-list-table', ['routePrefix' => 'coordinator'])
    </div>
    @endif
@endsection

@push('scripts')
<script>
function confirmDelete(id) {
    Swal.fire({
        title: 'Move to Recycle Bin?',
        text: 'This file will be removed from Documents and moved to your Recycle Bin. You can restore it later.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        customClass: { popup: 'swal-flat' }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('delete-doc-' + id).submit();
        }
    });
}
</script>
@endpush
