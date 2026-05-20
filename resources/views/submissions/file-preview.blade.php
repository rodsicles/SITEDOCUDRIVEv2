@extends('layouts.dashboard')

@section('title', $title . ' - Preview')
@section('page-title', $title)
@section('page-subtitle', $folderPath ?? 'File preview')

@section('sidebar')
    @if(auth()->user()->isFaculty())
        @include('partials.faculty-sidebar')
    @elseif(auth()->user()->isProgramCoordinator())
        @include('partials.coordinator-sidebar')
    @else
        @include('partials.dean-sidebar')
    @endif
@endsection

@section('content')
    <div class="content-card mb-4">
        <div class="p-4 flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                @if(!empty($documentsUrl))
                <a href="{{ $documentsUrl }}" class="text-lg font-semibold text-[#028a0f] dark:text-[#02b815] hover:underline break-words">
                    {{ $title }}
                </a>
                @else
                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200 break-words">{{ $title }}</h2>
                @endif
                @if(!empty($folderPath))
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 mb-0">
                    <i class="fas fa-folder-open mr-1 text-[#028a0f]"></i>
                    @if(!empty($documentsUrl))
                    <a href="{{ $documentsUrl }}" class="hover:underline text-gray-600 dark:text-gray-300">{{ $folderPath }}</a>
                    @else
                    {{ $folderPath }}
                    @endif
                </p>
                @endif
            </div>
            <div class="doc-action-btns shrink-0">
                <a href="{{ $streamUrl }}" target="_blank" class="btn btn-action-view text-xs">
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
        <div class="submission-preview-frame border-t border-gray-200 dark:border-gray-700">
            <iframe src="{{ $streamUrl }}" title="{{ $title }}" class="submission-preview-frame__iframe"></iframe>
        </div>
    </div>
@endsection
