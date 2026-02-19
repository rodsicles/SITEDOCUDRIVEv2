@extends('layouts.dashboard')

@section('title', 'Event Details')

@section('page-title', $event->title)
@section('page-subtitle', $event->event_type)

@section('content')
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Event Information</h3>
            <div class="flex gap-2.5">
                @if($event->created_by === auth()->id())
                <button class="btn btn-danger" onclick="deleteEvent({{ $event->event_id }})">
                    <i class="fas fa-trash"></i> Delete
                </button>
                @endif
                <a href="{{ route('calendar.index') }}" class="btn btn-primary">
                    <i class="fas fa-calendar"></i> Back to Calendar
                </a>
            </div>
        </div>

        <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <h4 class="text-[#028a0f] dark:text-[#4caf50] mb-5">
                        <i class="fas fa-info-circle"></i> Details
                    </h4>
                    
                    <div class="mb-4">
                        <strong>Event Type:</strong><br>
                        <span class="badge mt-1.5" style="background: {{ $event->event_type === 'Meeting' ? '#007bff' : '#6c757d' }}; color: white;">
                            {{ $event->event_type }}
                        </span>
                    </div>

                    <div class="mb-4">
                        <strong>Start:</strong><br>
                        {{ $event->start_datetime->format('F j, Y g:i A') }}
                    </div>

                    <div class="mb-4">
                        <strong>End:</strong><br>
                        {{ $event->end_datetime->format('F j, Y g:i A') }}
                    </div>

                    @if($event->location)
                    <div class="mb-4">
                        <strong>Location:</strong><br>
                        {{ $event->location }}
                    </div>
                    @endif

                    <div class="mb-4">
                        <strong>Visibility:</strong><br>
                        <span class="badge badge-info">{{ $event->visibility }}</span>
                    </div>

                    <div class="mb-4">
                        <strong>Created By:</strong><br>
                        {{ $event->creator->employee->full_name ?? $event->creator->username }}
                    </div>
                </div>

                <div>
                    <h4 class="text-[#028a0f] dark:text-[#4caf50] mb-5">
                        <i class="fas fa-users"></i> Attendees ({{ $event->attendees->count() }})
                    </h4>
                    
                    @if($event->attendees->count() > 0)
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($event->attendees as $attendee)
                                <tr>
                                    <td>{{ $attendee->user->employee->full_name ?? $attendee->user->username }}</td>
                                    <td>
                                        @if($attendee->response_status === 'Accepted')
                                            <span class="badge badge-success">Accepted</span>
                                        @elseif($attendee->response_status === 'Declined')
                                            <span class="badge badge-danger">Declined</span>
                                        @elseif($attendee->response_status === 'Maybe')
                                            <span class="badge badge-warning">Maybe</span>
                                        @else
                                            <span class="badge badge-info">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        
                        @if($event->hasAttendee(auth()->id()))
                        <div class="mt-5">
                            <form action="{{ route('calendar.respond', $event->event_id) }}" method="POST" class="flex gap-2.5">
                                @csrf
                                <button type="submit" name="response" value="Accepted" class="btn btn-success flex-1">
                                    <i class="fas fa-check"></i> Accept
                                </button>
                                <button type="submit" name="response" value="Maybe" class="btn btn-warning flex-1">
                                    <i class="fas fa-question"></i> Maybe
                                </button>
                                <button type="submit" name="response" value="Declined" class="btn btn-danger flex-1">
                                    <i class="fas fa-times"></i> Decline
                                </button>
                            </form>
                        </div>
                        @endif
                    @else
                        <p class="text-center text-gray-500 dark:text-gray-400 py-5">
                            No attendees invited
                        </p>
                    @endif
                </div>
            </div>

            @if($event->description)
            <div class="mt-8 pt-8 border-t-2 border-gray-200 dark:border-gray-700">
                <h4 class="text-[#028a0f] dark:text-[#4caf50] mb-4">
                    <i class="fas fa-align-left"></i> Description
                </h4>
                <p class="whitespace-pre-wrap">{{ $event->description }}</p>
            </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function deleteEvent(eventId) {
        if (confirm('Are you sure you want to delete this event? All attendees will be notified.')) {
            fetch(`/calendar/${eventId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                },
            })
            .then(response => {
                if (response.ok) {
                    window.location.href = '{{ route("calendar.index") }}';
                } else {
                    alert('Failed to delete event');
                }
            });
        }
    }
</script>
@endpush
