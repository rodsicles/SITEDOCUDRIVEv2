@extends('layouts.dashboard')
@section('title', 'Course history')
@section('page-title', 'Course history')
@section('page-subtitle', $course->label())
@section('sidebar')
    @include(auth()->user()->isProgramCoordinator() ? 'partials.coordinator-sidebar' : (auth()->user()->isSecretary() ? 'partials.secretary-sidebar' : 'partials.dean-sidebar'))
@endsection
@section('content')
<a href="{{ route(auth()->user()->isProgramCoordinator() ? 'coordinator.courses' : 'dean.courses') }}" class="btn btn-secondary mb-4">Back to Course Catalog</a>
<div class="content-card"><div class="card-header"><h2 class="card-title">Course changes</h2></div>
<div class="table-responsive"><table class="data-table"><thead><tr><th>Date / employee</th><th>Changes</th></tr></thead><tbody>
@forelse($changes as $change)
<tr><td>{{ $change->created_at->format('M d, Y g:i A') }}<br>{{ $change->actor?->employee?->full_name ?? $change->actor?->username ?? 'Former account' }}</td><td>
@foreach(['code' => 'Code', 'title' => 'Title', 'year_level' => 'Year level', 'semester' => 'Semester', 'lecture_units' => 'Lecture units', 'lab_units' => 'Lab units'] as $field => $label)
    @if(($change->before[$field] ?? null) !== ($change->after[$field] ?? null))<p><strong>{{ $label }}:</strong> {{ $change->before[$field] ?? 'Not specified' }} &rarr; {{ $change->after[$field] ?? 'Not specified' }}</p>@endif
@endforeach
</td></tr>
@empty
<tr><td colspan="2">No catalog edits recorded yet.</td></tr>
@endforelse
</tbody></table></div><div class="p-4">{{ $changes->links() }}</div></div>
@endsection
