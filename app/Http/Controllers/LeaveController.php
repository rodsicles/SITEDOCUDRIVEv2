<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveController extends Controller
{
    // Faculty: View leave requests
    public function index()
    {
        $user = auth()->user();
        
        if ($user->isFaculty()) {
            // Faculty sees only their own leaves
            $leaveRequests = LeaveRequest::where('user_id', $user->id)
                ->with('reviewer.employee')
                ->orderBy('created_at', 'desc')
                ->paginate(15);
        } else {
            // Coordinator/Dean sees all leave requests
            $leaveRequests = LeaveRequest::with(['user.employee', 'reviewer.employee'])
                ->orderBy('created_at', 'desc')
                ->paginate(15);
        }

        $leaveBalance = LeaveBalance::getOrCreateBalance($user->id);

        return view('leave.index', compact('leaveRequests', 'leaveBalance'));
    }

    // Faculty: Create leave request form
    public function create()
    {
        $leaveBalance = LeaveBalance::getOrCreateBalance(auth()->id());
        return view('leave.create', compact('leaveBalance'));
    }

    // Faculty: Store leave request
    public function store(Request $request)
    {
        $validated = $request->validate([
            'leave_type' => 'required|in:Sick Leave,Vacation Leave,Emergency Leave,Personal Leave,Study Leave,Maternity Leave,Paternity Leave,Other',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|min:10',
        ]);

        $startDate = new \DateTime($validated['start_date']);
        $endDate = new \DateTime($validated['end_date']);
        $daysCount = $startDate->diff($endDate)->days + 1;

        // Check leave balance
        $balance = LeaveBalance::getOrCreateBalance(auth()->id());
        
        if ((str_contains($validated['leave_type'], 'Sick') && $daysCount > $balance->getRemainingSickLeave()) ||
            (!str_contains($validated['leave_type'], 'Sick') && $daysCount > $balance->getRemainingVacationLeave())) {
            return back()->with('error', 'Insufficient leave balance for this request.');
        }

        $leaveRequest = LeaveRequest::create([
            'user_id' => auth()->id(),
            'leave_type' => $validated['leave_type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'days_count' => $daysCount,
            'reason' => $validated['reason'],
            'status' => 'Pending',
        ]);

        // Notify coordinators and dean
        $coordinatorsAndDeans = User::whereIn('role_id', [1, 2])->get();
        foreach ($coordinatorsAndDeans as $supervisor) {
            Notification::create([
                'user_id' => $supervisor->id,
                'message' => auth()->user()->username . ' filed a ' . $validated['leave_type'] . ' request (' . $daysCount . ' days)',
            ]);
        }

        return redirect()->route('leave.index')->with('success', 'Leave request submitted successfully.');
    }

    // Coordinator/Dean: Approve leave
    public function approve($id)
    {
        $leaveRequest = LeaveRequest::findOrFail($id);

        if (!auth()->user()->isProgramCoordinator() && !auth()->user()->isDean()) {
            return back()->with('error', 'Unauthorized action.');
        }

        $leaveRequest->update([
            'status' => 'Approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        // Deduct from leave balance
        $balance = LeaveBalance::getOrCreateBalance($leaveRequest->user_id);
        $balance->deductLeave($leaveRequest->leave_type, $leaveRequest->days_count);

        // Notify the faculty
        Notification::create([
            'user_id' => $leaveRequest->user_id,
            'message' => 'Your ' . $leaveRequest->leave_type . ' request has been APPROVED by ' . auth()->user()->username,
        ]);

        return back()->with('success', 'Leave request approved.');
    }

    // Coordinator/Dean: Reject leave
    public function reject(Request $request, $id)
    {
        $validated = $request->validate([
            'review_notes' => 'required|string|min:10',
        ]);

        $leaveRequest = LeaveRequest::findOrFail($id);

        if (!auth()->user()->isProgramCoordinator() && !auth()->user()->isDean()) {
            return back()->with('error', 'Unauthorized action.');
        }

        $leaveRequest->update([
            'status' => 'Rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $validated['review_notes'],
        ]);

        // Notify the faculty
        Notification::create([
            'user_id' => $leaveRequest->user_id,
            'message' => 'Your ' . $leaveRequest->leave_type . ' request has been REJECTED by ' . auth()->user()->username . '. Reason: ' . $validated['review_notes'],
        ]);

        return back()->with('success', 'Leave request rejected.');
    }

    // Leave Calendar View
    public function calendar()
    {
        $user = auth()->user();

        if ($user->isFaculty()) {
            // Faculty sees only their own approved leaves
            $leaves = LeaveRequest::where('user_id', $user->id)
                ->where('status', 'Approved')
                ->with('user.employee')
                ->get();
        } else {
            // Coordinator/Dean sees all approved leaves
            $leaves = LeaveRequest::where('status', 'Approved')
                ->with('user.employee')
                ->get();
        }

        // Format for calendar
        $events = $leaves->map(function($leave) {
            return [
                'title' => $leave->user->username . ' - ' . $leave->leave_type,
                'start' => $leave->start_date->format('Y-m-d'),
                'end' => $leave->end_date->addDay()->format('Y-m-d'), // FullCalendar end is exclusive
                'color' => '#dc3545',
                'description' => $leave->reason,
            ];
        });

        return view('leave.calendar', compact('events'));
    }
}
