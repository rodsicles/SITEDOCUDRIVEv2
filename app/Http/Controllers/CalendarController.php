<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\EventAttendee;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    // View calendar
    public function index()
    {
        $user = auth()->user();
        $events = CalendarEvent::getEventsForUser($user->id);
        
        // Format for FullCalendar
        $formattedEvents = $events->map(function($event) {
            $color = match($event->event_type) {
                'Meeting' => '#007bff',
                'Deadline' => '#dc3545',
                'Training' => '#28a745',
                'Conference' => '#6f42c1',
                'Holiday' => '#ffc107',
                'Seminar' => '#17a2b8',
                default => '#6c757d',
            };

            return [
                'id' => $event->event_id,
                'title' => $event->title,
                'start' => $event->start_datetime->format('Y-m-d\TH:i:s'),
                'end' => $event->end_datetime->format('Y-m-d\TH:i:s'),
                'color' => $color,
                'allDay' => $event->all_day,
                'description' => $event->description,
                'location' => $event->location,
                'type' => $event->event_type,
            ];
        });

        return view('calendar.index', compact('formattedEvents'));
    }

    // Create event form
    public function create()
    {
        $users = User::with('employee')->where('status', 'Active')->get();
        return view('calendar.create', compact('users'));
    }

    // Store event
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'event_type' => 'required|in:Meeting,Deadline,Training,Conference,Holiday,Seminar,Other',
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after:start_datetime',
            'location' => 'nullable|string|max:255',
            'all_day' => 'boolean',
            'visibility' => 'required|in:Public,Department,Private',
            'send_reminder' => 'boolean',
            'reminder_minutes' => 'nullable|integer|min:5',
            'attendees' => 'nullable|array',
            'attendees.*' => 'exists:users,id',
        ]);

        $event = CalendarEvent::create([
            'created_by' => auth()->id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'event_type' => $validated['event_type'],
            'start_datetime' => $validated['start_datetime'],
            'end_datetime' => $validated['end_datetime'],
            'location' => $validated['location'] ?? null,
            'all_day' => $validated['all_day'] ?? false,
            'visibility' => $validated['visibility'],
            'send_reminder' => $validated['send_reminder'] ?? true,
            'reminder_minutes' => $validated['reminder_minutes'] ?? 30,
        ]);

        // Add attendees
        if (!empty($validated['attendees'])) {
            foreach ($validated['attendees'] as $userId) {
                EventAttendee::create([
                    'event_id' => $event->event_id,
                    'user_id' => $userId,
                    'response_status' => 'Pending',
                ]);

                // Notify attendee
                Notification::create([
                    'user_id' => $userId,
                    'message' => 'You have been invited to: ' . $event->title . ' on ' . $event->start_datetime->format('M j, Y g:i A'),
                ]);
            }
        }

        return redirect()->route('calendar.index')->with('success', 'Event created successfully.');
    }

    // View event details
    public function show($id)
    {
        $event = CalendarEvent::with(['creator.employee', 'attendees.user.employee'])->findOrFail($id);
        
        // Check if user has permission to view
        $user = auth()->user();
        if ($event->visibility === 'Private' && $event->created_by !== $user->id && !$event->hasAttendee($user->id)) {
            abort(403, 'Unauthorized');
        }

        if ($event->visibility === 'Department' && $event->created_by !== $user->id && !$event->hasAttendee($user->id)) {
            $creatorDept = optional($event->creator->employee)->department;
            $viewerDept = optional($user->employee)->department;
            if (!$creatorDept || $creatorDept !== $viewerDept) {
                abort(403, 'Unauthorized');
            }
        }

        return view('calendar.show', compact('event'));
    }

    // Update event
    public function update(Request $request, $id)
    {
        $event = CalendarEvent::findOrFail($id);

        // Only creator can update
        if ($event->created_by !== auth()->id()) {
            return back()->with('error', 'You can only edit your own events.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'event_type' => 'required|in:Meeting,Deadline,Training,Conference,Holiday,Seminar,Other',
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after:start_datetime',
            'location' => 'nullable|string|max:255',
            'all_day' => 'boolean',
            'visibility' => 'required|in:Public,Department,Private',
        ]);

        $event->update($validated);

        return redirect()->route('calendar.show', $event->event_id)->with('success', 'Event updated successfully.');
    }

    // Delete event
    public function destroy($id)
    {
        $event = CalendarEvent::findOrFail($id);

        // Only creator or admin can delete
        if ($event->created_by !== auth()->id() && !auth()->user()->isDean()) {
            return back()->with('error', 'Unauthorized action.');
        }

        // Notify attendees
        foreach ($event->attendees as $attendee) {
            Notification::create([
                'user_id' => $attendee->user_id,
                'message' => 'Event cancelled: ' . $event->title . ' (scheduled for ' . $event->start_datetime->format('M j, Y') . ')',
            ]);
        }

        $event->delete();

        return redirect()->route('calendar.index')->with('success', 'Event deleted successfully.');
    }

    // Respond to event invitation
    public function respond(Request $request, $id)
    {
        $validated = $request->validate([
            'response' => 'required|in:Accepted,Declined,Maybe',
        ]);

        $attendee = EventAttendee::where('event_id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $attendee->updateResponse($validated['response']);

        // Notify creator
        Notification::create([
            'user_id' => $attendee->event->created_by,
            'message' => auth()->user()->username . ' ' . strtolower($validated['response']) . ' the event: ' . $attendee->event->title,
        ]);

        return back()->with('success', 'Response updated.');
    }

    // Get events as JSON (for AJAX calendar)
    public function getEvents(Request $request)
    {
        $start = $request->input('start');
        $end = $request->input('end');
        
        $events = CalendarEvent::getEventsForUser(auth()->id(), $start, $end);
        
        return response()->json($events->map(function($event) {
            return [
                'id' => $event->event_id,
                'title' => $event->title,
                'start' => $event->start_datetime->toIso8601String(),
                'end' => $event->end_datetime->toIso8601String(),
                'allDay' => $event->all_day,
                'url' => route('calendar.show', $event->event_id),
            ];
        }));
    }
}
