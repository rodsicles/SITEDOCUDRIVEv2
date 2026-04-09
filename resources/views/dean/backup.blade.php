@extends('layouts.dashboard')

@section('title', 'Backup & Restore - Dean')
@section('page-title', 'Backup & Restore')
@section('page-subtitle', 'Database backup and restore management')

@section('sidebar')
    @include('partials.dean-sidebar')
@endsection

@section('content')
    {{-- Create Backup --}}
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-database mr-2"></i>Database Backup</h3>
            <form action="{{ route('dean.backup.create') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary border-0" onclick="return confirm('Create a new database backup?')">
                    <i class="fas fa-download"></i> Create Backup Now
                </button>
            </form>
        </div>
        <div class="backup-info">
            <i class="fas fa-info-circle mr-1"></i>
            The backup includes all database tables and records. Backup files are stored securely on the server. You can download them for safekeeping.
        </div>
    </div>

    {{-- Backup History --}}
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-history mr-2"></i>Backup History</h3>
            <span class="badge badge-info">{{ $backups->count() }} Backups</span>
        </div>
        @if($backups->count() > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Filename</th>
                        <th>Size</th>
                        <th>Type</th>
                        <th>Created By</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($backups as $backup)
                    <tr>
                        <td><strong>{{ $backup->filename }}</strong></td>
                        <td>{{ $backup->getFormattedSize() }}</td>
                        <td>
                            @if($backup->type === 'export')
                                <span class="badge badge-info">Export</span>
                            @else
                                <span class="badge badge-warning">Import</span>
                            @endif
                        </td>
                        <td>{{ $backup->user->employee->full_name ?? $backup->user->username }}</td>
                        <td>{{ $backup->created_at->format('M d, Y h:i A') }}</td>
                        <td>
                            <div class="flex gap-1">
                                @if($backup->type === 'export')
                                    <a href="{{ route('dean.backup.download', $backup->filename) }}" class="btn btn-success py-1 px-2.5 text-xs border-0">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                @endif
                                <form action="{{ route('dean.backup.destroy', $backup->filename) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger py-1 px-2.5 text-xs border-0" onclick="return confirm('Delete this backup record?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="text-center py-10 text-gray-500 dark:text-gray-400">
                <i class="fas fa-database text-5xl mb-4 opacity-50"></i>
                <p>No backups created yet. Create your first backup above.</p>
            </div>
        @endif
    </div>

    {{-- Restore Section --}}
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-upload mr-2"></i>Restore Database</h3>
        </div>
        <div class="backup-warning">
            <strong><i class="fas fa-exclamation-triangle mr-1"></i> Warning:</strong>
            Restoring a database will overwrite ALL current data. This action cannot be undone. Make sure you have a recent backup before proceeding.
        </div>
        <form action="{{ route('dean.backup.restore') }}" method="POST" enctype="multipart/form-data" id="restoreForm">
            @csrf
            <div class="form-group">
                <label class="form-label">Upload .sql Backup File</label>
                <input type="file" name="backup_file" class="form-control" accept=".sql" required>
            </div>
            <div class="flex items-center gap-3 mb-5">
                <input type="checkbox" id="confirmRestore" class="w-4 h-4" onchange="document.getElementById('restoreBtn').disabled = !this.checked">
                <label for="confirmRestore" class="text-sm text-gray-700 dark:text-gray-300">
                    I understand this will overwrite the current database
                </label>
            </div>
            <button type="submit" id="restoreBtn" class="btn btn-danger border-0" disabled onclick="return confirm('Are you absolutely sure? This will overwrite all current data.')">
                <i class="fas fa-upload"></i> Restore Database
            </button>
        </form>
    </div>
@endsection
