<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use App\Models\DashboardLog;
use App\Models\User;

class LeaveService
{
    /**
     * Calculate the number of days between two dates (inclusive).
     */
    public function calculateDays(string $startDate, string $endDate): int
    {
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        return $start->diff($end)->days + 1;
    }

    /**
     * Create a leave record — auto-logged, balance deducted immediately.
     */
    public function createLeaveRequest(array $validated, User $user): LeaveRequest
    {
        $daysCount = $this->calculateDays($validated['start_date'], $validated['end_date']);

        $leaveRequest = LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type' => $validated['leave_type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'days_count' => $daysCount,
            'reason' => $validated['reason'],
            'status' => 'Approved',
        ]);

        // Deduct balance immediately on filing
        $balance = LeaveBalance::getOrCreateBalance($user->id);
        $balance->deductLeave($validated['leave_type'], $daysCount);

        DashboardLog::create([
            'user_id' => $user->id,
            'activity' => 'Logged leave: ' . $validated['leave_type'] . ' (' . $validated['start_date'] . ' to ' . $validated['end_date'] . ')',
            'activity_type' => 'leave_requested',
            'visibility' => 'coordinator',
        ]);

        return $leaveRequest;
    }

    /**
     * Update an existing leave record. Adjusts balance if days/type changed.
     */
    public function updateLeaveRequest(LeaveRequest $leaveRequest, array $validated): LeaveRequest
    {
        $oldDaysCount = $leaveRequest->days_count;
        $oldLeaveType = $leaveRequest->leave_type;
        $newDaysCount = $this->calculateDays($validated['start_date'], $validated['end_date']);

        // Restore old balance then deduct new
        $balance = LeaveBalance::getOrCreateBalance($leaveRequest->user_id);
        $balance->restoreLeave($oldLeaveType, $oldDaysCount);
        $balance->deductLeave($validated['leave_type'], $newDaysCount);

        $leaveRequest->update([
            'leave_type' => $validated['leave_type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'days_count' => $newDaysCount,
            'reason' => $validated['reason'],
        ]);

        return $leaveRequest;
    }

    /**
     * Delete a leave record and restore balance.
     */
    public function deleteLeaveRequest(LeaveRequest $leaveRequest, User $user): void
    {
        // Restore balance since it was deducted on creation
        $balance = LeaveBalance::getOrCreateBalance($leaveRequest->user_id);
        $balance->restoreLeave($leaveRequest->leave_type, $leaveRequest->days_count);

        DashboardLog::create([
            'user_id' => $user->id,
            'activity' => 'Deleted leave record: ' . $leaveRequest->leave_type . ' (' . $leaveRequest->days_count . ' days)',
            'activity_type' => 'leave_cancelled',
            'visibility' => 'coordinator',
        ]);

        $leaveRequest->delete();
    }
}
