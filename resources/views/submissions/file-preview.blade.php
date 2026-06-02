@extends('layouts.dashboard')

@section('title', $title . ' - Preview')
@section('page-title', $title)
@section('page-subtitle', $folderPath ?? 'File preview')

@section('sidebar')
    @if(auth()->user()->isFaculty())
        @include('partials.faculty-sidebar')
    @elseif(auth()->user()->isProgramCoordinator())
        @include('partials.coordinator-sidebar')
    @elseif(auth()->user()->isSecretary())
        @include('partials.secretary-sidebar')
    @else
        @include('partials.dean-sidebar')
    @endif
@endsection

@section('content')
    <div class="content-card submission-preview-card mb-4">
        <div class="submission-preview-toolbar">
            <div class="doc-action-btns">
                <a href="{{ $streamUrl }}" target="_blank" rel="noopener" class="btn btn-action-view text-xs">
                    <i class="fas fa-external-link-alt"></i> Open in tab
                </a>
                <a href="{{ $downloadUrl }}" class="btn btn-action-download text-xs">
                    <i class="fas fa-download"></i> Download
                </a>
                <a href="{{ $backUrl }}" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
        <div class="submission-preview-frame">
            <iframe src="{{ $streamUrl }}" title="{{ $title }}" class="submission-preview-frame__iframe"></iframe>
        </div>
    </div>
@endsection
