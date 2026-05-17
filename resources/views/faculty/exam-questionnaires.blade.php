@extends('layouts.dashboard')

@section('title', 'Exam Questionnaires - Faculty')
@section('page-title', 'Exam Questionnaires')
@section('page-subtitle', 'Submit your exam questionnaires for review')

@section('sidebar')
    @include('partials.faculty-sidebar')
@endsection

@section('content')

    {{-- Current Semester Info Banner --}}
    @php
        $month = now()->month;
        $year  = now()->year;
        if ($month >= 8) {
            $currentSem = '1st Semester';
            $ayStart    = $year;
        } else {
            $currentSem = '2nd Semester';
            $ayStart    = $year - 1;
        }
        $currentAY = "AY {$ayStart}-" . ($ayStart + 1);
    @endphp

    <div class="content-card mb-4" style="border-left: 3px solid #028a0f;">
        <div class="p-4 flex items-center gap-3">
            <div class="stat-icon-horizontal"><i class="fas fa-calendar-check"></i></div>
            <div>
                <div class="font-semibold text-gray-700 dark:text-gray-200">
                    Your submission will be tagged: <span style="color:#028a0f;">{{ $currentSem }} &bull; {{ $currentAY }}</span>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Semester and academic year are set automatically based on today's date.</div>
            </div>
        </div>
    </div>

    @php
        $useItSubjectPicker = \App\Support\IteSubjects::userIsInformationTechnology(auth()->user());
    @endphp

    {{-- Submit Form --}}
    <div class="content-card mb-5">
        <div class="card-header">
            <h3 class="card-title">Submit Exam Questionnaire</h3>
        </div>
        <form action="{{ route('faculty.exam-questionnaires.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-2 gap-4 p-4">
                @if($useItSubjectPicker)
                    @include('partials.ite-subject-picker', ['pickerId' => 'eqSubjectPicker'])
                @else
                <div class="form-group">
                    <label class="form-label">Subject <span class="text-red-500">*</span></label>
                    <input type="text" name="subject" class="form-control" maxlength="100" required
                           placeholder="e.g. Human Computer Interaction"
                           value="{{ old('subject') }}">
                    @error('subject')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
                @endif
                <div class="form-group">
                    <label class="form-label">Exam Type <span class="text-red-500">*</span></label>
                    <select name="exam_type" class="form-control" required>
                        <option value="">-- Select Exam Type --</option>
                        @foreach(['Quiz','Prelim','Midterm','Pre-Final','Final'] as $type)
                            <option value="{{ $type }}" {{ old('exam_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                    @error('exam_type')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
                <div class="form-group col-span-2">
                    <label class="form-label">File (PDF only, max 10MB) <span class="text-red-500">*</span></label>
                    <input type="file" name="file" class="form-control" accept=".pdf" required data-dropzone="1">
                    @error('file')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="px-4 pb-4">
                <button type="submit" class="btn btn-primary"
                        onclick="return typeof eqSubjectPickerValidate !== 'function' || eqSubjectPickerValidate();">
                    <i class="fas fa-paper-plane"></i> Submit for Review
                </button>
            </div>
        </form>
    </div>

    {{-- My Submissions --}}
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">My Submissions</h3>
            <span class="badge badge-info">{{ $questionnaires->total() }} Total</span>
        </div>

        <div class="documents-filter flex items-center gap-4 flex-wrap">
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium whitespace-nowrap text-gray-700 dark:text-gray-300">Status:</label>
                <select onchange="window.location.href=this.value" class="form-control text-sm">
                    <option value="{{ route('faculty.exam-questionnaires.index') }}">All</option>
                    @foreach(['pending','approved','rejected'] as $s)
                        <option value="{{ route('faculty.exam-questionnaires.index', ['status' => $s]) }}"
                            {{ $statusFilter === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <form action="{{ route('faculty.exam-questionnaires.index') }}" method="GET" class="flex items-center gap-2 ml-auto">
                @if($statusFilter)<input type="hidden" name="status" value="{{ $statusFilter }}">@endif
                <input type="text" name="search" value="{{ $search }}" class="form-control text-sm"
                       placeholder="Search subject..." style="min-width:200px">
                <button type="submit" class="btn btn-primary text-sm"><i class="fas fa-search"></i></button>
                @if($search)
                    <a href="{{ route('faculty.exam-questionnaires.index') }}"
                       class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </form>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-10"></th>
                    <th>Subject</th>
                    <th>Exam Type</th>
                    <th>Semester</th>
                    <th>Academic Year</th>
                    <th>Status</th>
                    <th>Remarks</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($questionnaires as $q)
                <tr>
                    <td>
                        <div class="w-9 h-9 flex items-center justify-center bg-gray-100 dark:bg-gray-700 text-lg">
                            @if($q->file_type === 'pdf')
                                <i class="fas fa-file-pdf text-red-700"></i>
                            @else
                                <i class="fas fa-file-word text-blue-700"></i>
                            @endif
                        </div>
                    </td>
                    <td>
                        <strong>{{ $q->subject }}</strong>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $q->title }}</div>
                    </td>
                    <td><span class="doc-category-badge">{{ $q->exam_type }}</span></td>
                    <td>{{ $q->semester }}</td>
                    <td>{{ $q->academic_year }}</td>
                    <td>
                        @if($q->isPending())
                            <span class="badge" style="background:#b45309;color:#fff;">Pending</span>
                        @elseif($q->isApproved())
                            <span class="badge badge-success">Approved</span>
                        @else
                            <span class="badge badge-danger">Rejected</span>
                        @endif
                    </td>
                    <td class="text-xs text-gray-500 dark:text-gray-400" style="max-width:150px;">
                        {{ $q->remarks ?? '—' }}
                    </td>
                    <td>{{ $q->created_at->format('M d, Y') }}</td>
                    <td>
                        <div class="doc-action-btns">
                            <a href="{{ route('faculty.exam-questionnaires.view', $q->id) }}" target="_blank" class="btn btn-primary text-xs">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="{{ route('faculty.exam-questionnaires.download', $q->id) }}" class="btn btn-success text-xs">
                                <i class="fas fa-download"></i> Download
                            </a>
                            @if($q->isPending())
                            <form action="{{ route('faculty.exam-questionnaires.destroy', $q->id) }}" method="POST"
                                  onsubmit="return confirm('Delete this submission?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger text-xs"><i class="fas fa-trash"></i></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-gray-500 dark:text-gray-400 py-8">
                        No submissions yet. Use the form above to submit your first questionnaire.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-5">{{ $questionnaires->links() }}</div>
    </div>
@endsection
