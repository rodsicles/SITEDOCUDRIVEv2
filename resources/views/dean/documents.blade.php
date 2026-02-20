@extends('layouts.dashboard')

@section('title', 'Documents - Dean')

@section('page-title', 'Documents')
@section('page-subtitle', 'View all uploaded documents')

@section('sidebar')
    <a href="{{ route('dean.dashboard') }}" class="menu-item">
        <i class="fas fa-chart-line"></i> Dashboard
    </a>
    <a href="{{ route('leave.index') }}" class="menu-item">
        <i class="fas fa-calendar-alt"></i> Leave Requests
    </a>
    <a href="{{ route('calendar.index') }}" class="menu-item">
        <i class="fas fa-calendar"></i> Calendar
    </a>
    <a href="{{ route('dean.employees') }}" class="menu-item">
        <i class="fas fa-users"></i> Employees
    </a>
    <a href="{{ route('dean.reports') }}" class="menu-item">
        <i class="fas fa-file-alt"></i> Performance Reports
    </a>
    <a href="{{ route('dean.analytics') }}" class="menu-item">
        <i class="fas fa-chart-pie"></i> Analytics
    </a>
    <a href="{{ route('dean.documents') }}" class="menu-item active">
        <i class="fas fa-folder"></i> Documents
    </a>
@endsection

@section('content')
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">All Documents</h3>
            <span class="badge-info">{{ $documents->total() }} Files</span>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 50px;"></th>
                    <th>Document Title</th>
                    <th>Type</th>
                    <th>Uploaded By</th>
                    <th>Upload Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($documents as $document)
                <tr>
                    <td class="text-center text-xl">
                        @php
                            $extension = strtolower(pathinfo($document->file_path, PATHINFO_EXTENSION));
                        @endphp
                        @if($extension === 'pdf')
                            <i class="fas fa-file-pdf" style="color: #d32f2f;"></i>
                        @elseif(in_array($extension, ['png', 'jpg', 'jpeg']))
                            <i class="fas fa-file-image" style="color: #1976d2;"></i>
                        @else
                            <i class="fas fa-file" style="color: #757575;"></i>
                        @endif
                    </td>
                    <td><strong>{{ $document->document_title }}</strong></td>
                    <td>
                        @if($document->category)
                            @php
                                $categoryColors = [
                                    'Policies' => '#1976d2',
                                    'Forms' => '#388e3c',
                                    'Reports' => '#d32f2f',
                                    'Memos' => '#f57c00',
                                    'Research Papers' => '#7b1fa2',
                                    'Other' => '#616161',
                                ];
                                $color = $categoryColors[$document->category] ?? '#616161';
                            @endphp
                            <span class="badge" style="background: {{ $color }}20; color: {{ $color }};">
                                {{ $document->category }}
                            </span>
                        @elseif($document->document_type === 'pdf')
                            <span class="badge badge-danger">PDF Document</span>
                        @elseif($document->document_type === 'image')
                            <span class="badge badge-info">Image File</span>
                        @else
                            <span class="badge badge-info">{{ $document->document_type ?? 'General' }}</span>
                        @endif
                    </td>
                    <td>{{ $document->uploader->employee->full_name ?? $document->uploader->username }}</td>
                    <td>{{ $document->created_at->format('M d, Y h:i A') }}</td>
                    <td>
                        <a href="{{ route('dean.view-document', $document->document_id) }}" target="_blank" class="btn btn-primary text-xs mr-1.5">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <a href="{{ route('dean.download-document', $document->document_id) }}" class="btn btn-success text-xs">
                            <i class="fas fa-download"></i> Download
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-gray-500 dark:text-gray-400">
                        No documents available
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        <div class="mt-5">
            {{ $documents->links() }}
        </div>
    </div>
@endsection
