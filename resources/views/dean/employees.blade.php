@extends('layouts.dashboard')

@section('title', 'Employees - Dean')

@section('page-title', 'All Employees')
@section('page-subtitle', 'Manage and view all employee information')

@section('sidebar')
    <a href="{{ route('dean.dashboard') }}" class="menu-item">
        <i class="fas fa-chart-line"></i> Dashboard
    </a>
    <a href="{{ route('dean.employees') }}" class="menu-item active">
        <i class="fas fa-users"></i> Employees
    </a>
    <a href="{{ route('dean.reports') }}" class="menu-item">
        <i class="fas fa-file-alt"></i> Performance Reports
    </a>
    <a href="{{ route('dean.analytics') }}" class="menu-item">
        <i class="fas fa-chart-pie"></i> Analytics
    </a>
    <a href="{{ route('dean.documents') }}" class="menu-item">
        <i class="fas fa-folder"></i> Documents
    </a>
@endsection

@section('content')
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Employee Directory</h3>
            <span class="badge badge-info">{{ $employees->total() }} Total</span>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Employee No.</th>
                    <th>Full Name</th>
                    <th>Department</th>
                    <th>Position</th>
                    <th>Role</th>
                    <th>Action</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $employee)
                <tr>
                    <td><strong>{{ $employee->employee_no ?? 'N/A' }}</strong></td>
                    <td>{{ $employee->full_name }}</td>
                    <td>{{ $employee->department ?? 'N/A' }}</td>
                    <td>{{ $employee->position ?? 'N/A' }}</td>
                    <td>
                        <span class="badge badge-info">
                            {{ $employee->user->role->role_name }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('dean.employee-profile', $employee->employee_id) }}" class="btn btn-primary text-xs">
                            <i class="fas fa-eye"></i> View Profile
                        </a>
                    </td>
                    <td>
                        @if($employee->user->status === 'Active')
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-danger">Inactive</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-gray-500 dark:text-gray-400">
                        No employees found
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        <div class="mt-5">
            {{ $employees->links() }}
        </div>
    </div>
@endsection
