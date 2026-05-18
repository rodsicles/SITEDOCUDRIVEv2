@extends('layouts.dashboard')

@section('title', 'Archives')
@section('page-title', 'Archives')
@section('page-subtitle', 'Browse archived school years')

@section('sidebar')
    @include('partials.' . $role . '-sidebar')
@endsection

@section('content')
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-box-archive mr-2"></i>Archived School Years</h3>
            <span class="badge badge-info">{{ $archivedYears->count() }} Archives</span>
        </div>
        @if($archivedYears->isEmpty())
            <div class="p-6 text-center text-gray-500 dark:text-gray-400">
                <i class="fas fa-inbox text-4xl mb-3"></i>
                <p>No archived school years yet.</p>
            </div>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>School Year</th>
                        <th>Documents</th>
                        <th>Teaching Guides</th>
                        <th>Exam Questionnaires</th>
                        <th>Archived On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($archivedYears as $year)
                    <tr>
                        <td><strong>{{ $year->name }}</strong></td>
                        <td>{{ $year->documents_count }}</td>
                        <td>{{ $year->teaching_guides_count }}</td>
                        <td>{{ $year->exam_questionnaires_count }}</td>
                        <td>{{ $year->archived_at->format('M d, Y') }}</td>
                        <td>
                            <a href="{{ route($role . '.archives.show', $year->id) }}" class="btn btn-sm btn-primary border-0">
                                <i class="fas fa-eye"></i> Browse
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
