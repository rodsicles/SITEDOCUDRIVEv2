<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use App\Services\LeaveService;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function __construct(
        protected LeaveService $leaveService
    ) {}

    public function index()
    {
        $user = auth()->user();

        if ($user->isFaculty()) {
            $leaveRequests = LeaveRequest::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate(15);
        } else {
            $leaveRequests = LeaveRequest::with(['user.employee'])
                ->orderBy('created_at', 'desc')
                ->paginate(15);
        }

        $leaveBalance = LeaveBalance::getOrCreateBalance($user->id);

        return view('leave.index', compact('leaveRequests', 'leaveBalance'));
    }

    public function create()
    {
        $leaveBalance = LeaveBalance::getOrCreateBalance(auth()->id());
        return view('leave.create', compact('leaveBalance'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'leave_type' => 'required|in:Sick Leave,Vacation Leave,Emergency Leave,Personal Leave,Study Leave,Maternity Leave,Paternity Leave,Other',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|min:10|max:120',
        ]);

        $this->leaveService->createLeaveRequest($validated, auth()->user());

        return redirect()->route('leave.index')->with('success', 'Leave record logged successfully.');
    }

    public function edit($id)
    {
        $leaveRequest = LeaveRequest::findOrFail($id);

        if ($leaveRequest->user_id !== auth()->id()) {
            return redirect()->route('leave.index')->with('error', 'Cannot edit this leave record.');
        }

        $leaveBalance = LeaveBalance::getOrCreateBalance(auth()->id());
        return view('leave.edit', compact('leaveRequest', 'leaveBalance'));
    }

    public function update(Request $request, $id)
    {
        $leaveRequest = LeaveRequest::findOrFail($id);

        if ($leaveRequest->user_id !== auth()->id()) {
            return redirect()->route('leave.index')->with('error', 'Cannot update this leave record.');
        }

        $validated = $request->validate([
            'leave_type' => 'required|in:Sick Leave,Vacation Leave,Emergency Leave,Personal Leave,Study Leave,Maternity Leave,Paternity Leave,Other',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|min:10|max:120',
        ]);

        $this->leaveService->updateLeaveRequest($leaveRequest, $validated);

        return redirect()->route('leave.index')->with('success', 'Leave record updated successfully.');
    }

    public function delete($id)
    {
        $leaveRequest = LeaveRequest::findOrFail($id);

        if ($leaveRequest->user_id !== auth()->id()) {
            return back()->with('error', 'Unauthorized action.');
        }

        $this->leaveService->deleteLeaveRequest($leaveRequest, auth()->user());

        return back()->with('success', 'Leave record deleted and balance restored.');
    }

    public function calendar()
    {
        $user = auth()->user();

        if ($user->isFaculty()) {
            $leaves = LeaveRequest::where('user_id', $user->id)
                ->where('status', 'Approved')
                ->with('user.employee')
                ->get();
        } else {
            $leaves = LeaveRequest::where('status', 'Approved')
                ->with('user.employee')
                ->get();
        }

        $events = $leaves->map(function($leave) {
            $colors = [
                'Sick Leave' => '#ef4444',
                'Vacation Leave' => '#3b82f6',
                'Emergency Leave' => '#f97316',
                'Personal Leave' => '#8b5cf6',
                'Study Leave' => '#f59e0b',
                'Maternity Leave' => '#ec4899',
                'Paternity Leave' => '#06b6d4',
                'Other' => '#6b7280'
            ];

            return [
                'title' => $leave->user->username . ' - ' . $leave->leave_type,
                'start' => $leave->start_date->format('Y-m-d'),
                'end' => $leave->end_date->addDay()->format('Y-m-d'),
                'color' => $colors[$leave->leave_type] ?? '#dc2626',
                'description' => $leave->reason,
                'textColor' => '#ffffff',
                'classNames' => ['leave-event'],
                'extendedProps' => [
                    'leaveType' => $leave->leave_type,
                    'employeeName' => $leave->user->employee->full_name ?? $leave->user->username,
                    'days' => $leave->days_count
                ]
            ];
        });

        return view('leave.calendar', compact('events'));
    }
}
