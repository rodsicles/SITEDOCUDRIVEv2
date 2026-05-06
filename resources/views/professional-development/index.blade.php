@extends('layouts.dashboard')

@section('title', 'Professional Development')
@section('page-title', 'Professional Development')
@section('page-subtitle', 'Track seminars, trainings, and certifications')

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
    {{-- Stats --}}
    <div class="stats-grid-horizontal">
        <div class="stat-item-horizontal">
            <div class="stat-icon-horizontal"><i class="fas fa-graduation-cap"></i></div>
            <div class="stat-content-horizontal">
                <div class="stat-number-label"><strong>{{ $totalTrainings }}</strong> Total Trainings</div>
            </div>
        </div>
        <div class="stat-item-horizontal">
            <div class="stat-icon-horizontal"><i class="fas fa-clock"></i></div>
            <div class="stat-content-horizontal">
                <div class="stat-number-label"><strong>{{ number_format($totalHours, 1) }}</strong> Total Hours</div>
            </div>
        </div>
    </div>

    @if(auth()->user()->isFaculty())
        {{-- Faculty: Add Training Form --}}
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus mr-2"></i>Add Training Record</h3>
            </div>
            <form action="{{ route('professional-development.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                    <div class="form-group mb-0">
                        <label class="form-label">Seminar/Training Name *</label>
                        <input type="text" name="seminar_name" class="form-control" required maxlength="150" value="{{ old('seminar_name') }}">
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">Organizer *</label>
                        <input type="text" name="organizer" class="form-control" required maxlength="150" value="{{ old('organizer') }}">
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">Date Attended *</label>
                        <input type="date" name="date_attended" class="form-control" required value="{{ old('date_attended') }}">
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">Hours *</label>
                        <input type="number" name="hours" class="form-control" required step="0.5" min="0.5" max="999" value="{{ old('hours') }}">
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">Certificate (Image only: JPG, PNG, max 5MB)</label>
                        <input type="file" name="certificate" class="form-control" accept=".jpg,.jpeg,.png" data-dropzone="1">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary border-0">
                    <i class="fas fa-save"></i> Save Training Record
                </button>
            </form>
        </div>

        {{-- Faculty: My Records --}}
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list mr-2"></i>My Training Records</h3>
                <span class="badge badge-info">{{ $records->total() }} Records</span>
            </div>
            @if($records->count() > 0)
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Seminar Name</th>
                            <th>Date</th>
                            <th>Organizer</th>
                            <th>Hours</th>
                            <th>Certificate</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($records as $record)
                        <tr>
                            <td><strong>{{ $record->seminar_name }}</strong></td>
                            <td>{{ $record->date_attended->format('M d, Y') }}</td>
                            <td>{{ $record->organizer }}</td>
                            <td>{{ $record->hours }} hrs</td>
                            <td>
                                @if($record->hasCertificate())
                                    <span class="cert-status-uploaded"><i class="fas fa-check-circle"></i> Uploaded</span>
                                @else
                                    <span class="cert-status-pending"><i class="fas fa-clock"></i> Not Yet</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex gap-1">
                                    @if($record->hasCertificate())
                                        <a href="{{ asset($record->certificate_path) }}" target="_blank" class="btn btn-primary py-1 px-2.5 text-xs">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    @endif
                                    <form action="{{ route('professional-development.destroy', $record->professional_development_id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger py-1 px-2.5 text-xs border-0" onclick="return confirm('Delete this record?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mt-5">{{ $records->links() }}</div>
            @else
                <div class="text-center py-10 text-gray-500 dark:text-gray-400">
                    <i class="fas fa-graduation-cap text-5xl mb-4 opacity-50"></i>
                    <p>No training records yet. Add your first record above.</p>
                </div>
            @endif
        </div>
    @else
        {{-- Dean/Coordinator: Summary --}}
        @if(isset($summary) && $summary->count() > 0)
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i>Department Training Summary</h3>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Faculty Name</th>
                        <th>Department</th>
                        <th>Total Trainings</th>
                        <th>Total Hours</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($summary as $item)
                    <tr>
                        <td><strong>{{ $item->full_name }}</strong></td>
                        <td>{{ $item->department ?? 'N/A' }}</td>
                        <td>{{ $item->total_trainings }}</td>
                        <td>{{ number_format($item->total_hours, 1) }} hrs</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- Dean/Coordinator: All Records --}}
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list mr-2"></i>All Training Records</h3>
                <span class="badge badge-info">{{ $records->total() }} Records</span>
            </div>
            @if($records->count() > 0)
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Faculty Name</th>
                            <th>Seminar Name</th>
                            <th>Date</th>
                            <th>Organizer</th>
                            <th>Hours</th>
                            <th>Certificate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($records as $record)
                        <tr>
                            <td><strong>{{ $record->user->employee->full_name ?? $record->user->username }}</strong></td>
                            <td>{{ $record->seminar_name }}</td>
                            <td>{{ $record->date_attended->format('M d, Y') }}</td>
                            <td>{{ $record->organizer }}</td>
                            <td>{{ $record->hours }} hrs</td>
                            <td>
                                @if($record->hasCertificate())
                                    <a href="{{ asset($record->certificate_path) }}" target="_blank" class="cert-status-uploaded"><i class="fas fa-check-circle"></i> View</a>
                                @else
                                    <span class="cert-status-pending"><i class="fas fa-clock"></i> Not Yet</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mt-5">{{ $records->links() }}</div>
            @else
                <div class="text-center py-10 text-gray-500 dark:text-gray-400">
                    <i class="fas fa-graduation-cap text-5xl mb-4 opacity-50"></i>
                    <p>No training records found.</p>
                </div>
            @endif
        </div>
    @endif
@endsection
