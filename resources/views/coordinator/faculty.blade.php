@extends('layouts.dashboard')

@section('title', 'Faculty Members - Coordinator')

@section('page-title', 'Faculty Management')
@section('page-subtitle', 'View faculty employee accounts')

@section('sidebar')
    @include('partials.coordinator-sidebar')
@endsection

@section('content')
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Faculty Directory</h3>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Employee No.</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Action</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($facultyMembers as $faculty)
                <tr>
                    <td><strong>{{ $faculty->employee->employee_no ?? 'N/A' }}</strong></td>
                    <td>{{ $faculty->employee->full_name ?? 'N/A' }}</td>
                    <td>{{ $faculty->email }}</td>
                    <td>{{ $faculty->employee->department ?? 'N/A' }}</td>
                    <td>
                        <a href="{{ route('coordinator.faculty-profile', $faculty->employee->employee_id) }}" class="btn btn-primary text-xs px-4 py-2">
                            <i class="fas fa-eye"></i> View Profile
                        </a>
                    </td>
                    <td>
                        @if($faculty->status === 'Active')
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-danger">Inactive</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-gray-600 dark:text-gray-400">
                        No faculty members yet
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-5">
            {{ $facultyMembers->links() }}
        </div>
    </div>
@endsection
