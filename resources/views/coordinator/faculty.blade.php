@extends('layouts.dashboard')

@section('title', 'Faculty Members - Coordinator')

@section('page-title', 'Faculty Management')
@section('page-subtitle', 'Manage faculty employee accounts')

@section('sidebar')
    <a href="{{ route('coordinator.dashboard') }}" class="menu-item">
        <i class="fas fa-chart-line"></i> Dashboard
    </a>
    <a href="{{ route('coordinator.tasks') }}" class="menu-item">
        <i class="fas fa-tasks"></i> Tasks
    </a>
    <a href="{{ route('coordinator.faculty') }}" class="menu-item active">
        <i class="fas fa-users"></i> Faculty Members
    </a>
    <a href="{{ route('coordinator.documents') }}" class="menu-item">
        <i class="fas fa-folder"></i> Documents
    </a>
@endsection

@section('content')
    <!-- Tab Navigation -->
    <div class="mb-6">
        <div class="flex gap-2 border-b-2 border-gray-200 dark:border-gray-700">
            <button class="tab-button inline-flex items-center gap-2 px-5 py-3.5 bg-transparent border-0 border-b-3 border-transparent text-gray-600 dark:text-gray-400 text-sm font-semibold cursor-pointer transition-all duration-300 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 active:text-primary-600 active:border-primary-600 active:bg-primary-100 dark:active:bg-primary-900/30" onclick="switchTab('list')" id="listTab">
                <i class="fas fa-users"></i> Faculty Directory
            </button>
            <button class="tab-button inline-flex items-center gap-2 px-5 py-3.5 bg-transparent border-0 border-b-3 border-transparent text-gray-600 dark:text-gray-400 text-sm font-semibold cursor-pointer transition-all duration-300 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20" onclick="switchTab('create')" id="createTab">
                <i class="fas fa-user-plus"></i> Create New Faculty
            </button>
        </div>
    </div>

    <!-- Tab Content: Faculty List -->
    <div class="tab-content active" id="listContent">
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Faculty Directory</h3>
            </div>

            @if(session('success'))
                <div class="alert alert-success">
                    <strong><i class="fas fa-check-circle"></i> Success!</strong>
                    <p>{{ session('success') }}</p>
                </div>
            @endif

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
                    <td colspan="7" class="text-center text-gray-600 dark:text-gray-400">
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
    </div>

    <!-- Tab Content: Create Faculty Form -->
    <div class="tab-content" id="createContent" style="display: none;">
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Faculty Account Information</h3>
            </div>

            @if($errors->any())
                <div class="alert alert-error">
                    <strong><i class="fas fa-exclamation-circle"></i> Validation Errors:</strong>
                    <ul class="mt-2 ml-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            <form action="{{ route('coordinator.store-faculty') }}" method="POST">
                @csrf
                
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" 
                           placeholder="Enter full name" required maxlength="100" value="{{ old('full_name') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Employee Number</label>
                    <input type="text" name="employee_no" class="form-control" 
                           placeholder="Enter employee number (optional)" maxlength="30" value="{{ old('employee_no') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Department *</label>
                    <select name="department" class="form-control" required>
                        <option value="">Select Department</option>
                        <option value="Engineering" {{ old('department') == 'Engineering' ? 'selected' : '' }}>Engineering</option>
                        <option value="Information Technology" {{ old('department') == 'Information Technology' ? 'selected' : '' }}>Information Technology</option>
                    </select>
                    <small class="text-gray-600 dark:text-gray-400 text-xs mt-1 block">
                        <i class="fas fa-info-circle"></i> Only Engineering and Information Technology departments available
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-label">Username *</label>
                    <input type="text" name="username" class="form-control" 
                           placeholder="Enter username" required maxlength="50" value="{{ old('username') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address *</label>
                    <input type="email" name="email" class="form-control" 
                           placeholder="Enter email address" required maxlength="100" value="{{ old('email') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Password *</label>
                    <input type="password" name="password" class="form-control" 
                           placeholder="Enter password (min 8 characters)" required minlength="8">
                </div>

                <div class="flex gap-2.5">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-user-plus"></i> Create Faculty Account
                    </button>
                    <button type="button" class="btn bg-gray-600 hover:bg-gray-700 text-white"  onclick="switchTab('list')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.style.display = 'none';
            });

            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
                button.style.color = '';
                button.style.borderBottomColor = '';
                button.style.background = '';
            });

            // Show selected tab content and activate button
            if (tabName === 'list') {
                document.getElementById('listContent').style.display = 'block';
                const listTab = document.getElementById('listTab');
                listTab.classList.add('active');
                listTab.style.color = 'var(--color-primary)';
                listTab.style.borderBottomColor = 'var(--color-primary)';
                listTab.style.background = 'rgba(2, 138, 15, 0.1)';
            } else if (tabName === 'create') {
                document.getElementById('createContent').style.display = 'block';
                const createTab = document.getElementById('createTab');
                createTab.classList.add('active');
                createTab.style.color = 'var(--color-primary)';
                createTab.style.borderBottomColor = 'var(--color-primary)';
                createTab.style.background = 'rgba(2, 138, 15, 0.1)';
            }
        }

        // Check if there are validation errors, if so, show create tab
        @if($errors->any())
            document.addEventListener('DOMContentLoaded', function() {
                switchTab('create');
            });
        @endif
    </script>
@endsection
