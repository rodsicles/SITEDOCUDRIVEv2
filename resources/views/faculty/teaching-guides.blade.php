@extends('layouts.dashboard')

@section('title', 'Teaching Guides - Faculty')
@section('page-title', 'Teaching Guides')
@section('page-subtitle', 'View and download teaching guides')

@section('sidebar')
    @include('partials.faculty-sidebar')
@endsection

@section('content')
    {{-- Current Semester Info --}}
    @php
        $month = now()->month;
        $year  = now()->year;
        $currentSem = $month >= 8 ? '1st Semester' : '2nd Semester';
        $ayStart = $month >= 8 ? $year : $year - 1;
        $currentAY = "AY {$ayStart}-" . ($ayStart + 1);
    @endphp
    <div class="content-card mb-4" style="border-left: 3px solid #028a0f;">
        <div class="p-4 flex items-center gap-3">
            <div class="stat-icon-horizontal"><i class="fas fa-info-circle"></i></div>
            <div>
                <div class="font-semibold text-gray-700 dark:text-gray-200">Current: {{ $currentSem }} {{ $currentAY }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Teaching guides are uploaded by your Dean, Secretary, or Program Coordinator.</div>
            </div>
        </div>
    </div>

    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Available Teaching Guides</h3>
            <span class="badge badge-info">{{ $guides->total() }} Files</span>
        </div>

        <div class="documents-filter flex items-center gap-4 flex-wrap">
            <form action="{{ route('faculty.teaching-guides.index') }}" method="GET" class="flex items-center gap-2 ml-auto">
                <input type="text" name="search" value="{{ $search }}" class="form-control text-sm" placeholder="Search title or subject..." style="min-width:220px">
                <button type="submit" class="btn btn-primary text-sm"><i class="fas fa-search"></i></button>
                @if($search)
                    <a href="{{ route('faculty.teaching-guides.index') }}" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm"><i class="fas fa-times"></i></a>
                @endif
            </form>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-10"></th>
                    <th>Title</th>
                    <th>Subject</th>
                    <th>Folder</th>
                    <th>Date Uploaded</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($guides as $guide)
                <tr>
                    <td>
                        <div class="w-9 h-9 flex items-center justify-center bg-gray-100 dark:bg-gray-700 text-lg">
                            @if($guide->file_type === 'pdf')
                                <i class="fas fa-file-pdf text-red-700"></i>
                            @else
                                <i class="fas fa-file-word text-blue-700"></i>
                            @endif
                        </div>
                    </td>
                    <td><strong>{{ $guide->title }}</strong></td>
                    <td><span class="doc-category-badge">{{ $guide->subject }}</span></td>
                    <td class="text-xs text-gray-600 dark:text-gray-400">{{ $guide->folder?->folder_name ?? 'â€”' }}</td>
                    <td>{{ $guide->created_at->format('M d, Y') }}</td>
                    <td>
                        <a href="{{ route('faculty.teaching-guides.download', $guide->id) }}" class="btn btn-action-download text-xs">
                                <i class="fas fa-download"></i> Download
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-gray-500 dark:text-gray-400 py-8">No teaching guides available yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-5">{{ $guides->links() }}</div>
    </div>
@endsection
