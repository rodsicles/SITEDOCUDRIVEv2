@extends('layouts.dashboard')

@section('title', 'School Year Archives - Dean')
@section('page-title', 'School Year Archives')
@section('page-subtitle', 'Manage school year archiving')

@section('sidebar')
    @include('partials.dean-sidebar')
@endsection

@section('content')
    {{-- Active School Year --}}
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-calendar-alt mr-2"></i>Current School Year</h3>
            <span class="badge badge-success">Active</span>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">School Year</p>
                    <p class="text-lg font-bold text-gray-800 dark:text-white">{{ $activeSchoolYear->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Documents</p>
                    <p class="text-lg font-bold text-gray-800 dark:text-white">{{ $activeDocCount }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Teaching Guides</p>
                    <p class="text-lg font-bold text-gray-800 dark:text-white">{{ $activeTgCount }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Exam Questionnaires</p>
                    <p class="text-lg font-bold text-gray-800 dark:text-white">{{ $activeEqCount }}</p>
                </div>
            </div>

            <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-4">
                <button type="button" class="btn btn-danger border-0" onclick="document.getElementById('archiveModal').classList.remove('hidden')">
                    <i class="fas fa-archive"></i> Archive This School Year
                </button>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    This will archive all current documents, teaching guides, and exam questionnaires, then start a new clean school year.
                </p>
            </div>
        </div>
    </div>

    {{-- Archived School Years --}}
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-box-archive mr-2"></i>Archived School Years</h3>
            <span class="badge badge-info">{{ $archivedYears->count() }} Archives</span>
        </div>
        @if($archivedYears->isEmpty())
            <div class="p-6 text-center text-gray-500 dark:text-gray-400">
                <i class="fas fa-inbox text-4xl mb-3"></i>
                <p>No archived school years yet. When you archive the current school year, it will appear here.</p>
            </div>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>School Year</th>
                        <th>Archived On</th>
                        <th>Archived By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($archivedYears as $year)
                    <tr>
                        <td><strong>{{ $year->name }}</strong></td>
                        <td>{{ $year->archived_at->format('M d, Y h:i A') }}</td>
                        <td>{{ optional($year->archivedByUser)->employee->full_name ?? optional($year->archivedByUser)->username ?? 'System' }}</td>
                        <td>
                            <a href="{{ route('dean.archives.show', $year->id) }}" class="btn btn-sm btn-primary border-0">
                                <i class="fas fa-eye"></i> Browse
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Archive Modal --}}
    <div id="archiveModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-[#1e1e1e] rounded-lg shadow-xl max-w-lg w-full">
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">
                    <i class="fas fa-archive text-red-500 mr-2"></i>Archive School Year
                </h3>

                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded p-3 mb-4">
                    <p class="text-sm text-yellow-800 dark:text-yellow-200">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <strong>Warning:</strong> This action will archive all documents, teaching guides, and exam questionnaires from the current school year. User-created folders will also be archived. The system will start fresh with empty default folders.
                    </p>
                </div>

                <form action="{{ route('dean.archives.archive') }}" method="POST" id="archiveForm">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="text-sm font-semibold text-gray-600 dark:text-gray-300 block mb-1">Archive Name</label>
                            <input type="text" name="archive_name" value="{{ $activeSchoolYear->name }}" class="form-control" required maxlength="50">
                            <p class="text-xs text-gray-400 mt-1">Name for the archived school year</p>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gray-600 dark:text-gray-300 block mb-1">New School Year Name</label>
                            <input type="text" name="new_name" value="S.Y. {{ $suggestedStartYear }}-{{ $suggestedStartYear + 1 }}" class="form-control" required maxlength="50">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gray-600 dark:text-gray-300 block mb-1">New School Year Start</label>
                            <input type="number" name="new_start_year" value="{{ $suggestedStartYear }}" class="form-control" required min="2020" max="2099">
                            <p class="text-xs text-gray-400 mt-1">The start year of the new school year (e.g. {{ $suggestedStartYear }} for {{ $suggestedStartYear }}-{{ $suggestedStartYear + 1 }})</p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300" onclick="document.getElementById('archiveModal').classList.add('hidden')">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-danger border-0" onclick="return confirm('Are you absolutely sure? This cannot be undone.')">
                            <i class="fas fa-archive"></i> Confirm Archive
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
