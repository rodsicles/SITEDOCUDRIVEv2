<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use App\Models\Notification;
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
     * Check if the user has sufficient leave balance.
     * Returns true if sufficient, false otherwise.
     */
    public function hasSufficientBalance(int $userId, string $leaveType, int $daysCount): bool
    {
        $balance = LeaveBalance::getOrCreateBalance($userId);

        if (str_contains($leaveType, 'Sick')) {
            return $daysCount <= $balance->getRemainingSickLeave();
        }

        return $daysCount <= $balance->getRemainingVacationLeave();
    }

    /**
     * Create a leave request and notify supervisors.
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
            'status' => 'Pending',
        ]);

        $this->notifySupervisors(
            $user->username . ' filed a ' . $validated['leave_type'] . ' request (' . $daysCount . ' days)'
        );

        return $leaveRequest;
    }

    /**
     * Update an existing pending leave request.
     */
    public function updateLeaveRequest(LeaveRequest $leaveRequest, array $validated): LeaveRequest
    {
        $daysCount = $this->calculateDays($validated['start_date'], $validated['end_date']);

        $leaveRequest->update([
            'leave_type' => $validated['leave_type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'days_count' => $daysCount,
            'reason' => $validated['reason'],
        ]);

        return $leaveRequest;
    }

    /**
     * Cancel a pending leave request and notify supervisors.
     */
    public function cancelLeaveRequest(LeaveRequest $leaveRequest, User $user): void
    {
        $leaveRequest->update(['status' => 'Cancelled']);

        $this->notifySupervisors(
            $user->username . ' cancelled a ' . $leaveRequest->leave_type . ' request (' . $leaveRequest->days_count . ' days)'
        );
    }

    /**
     * Approve a leave request: update status, deduct balance, notify faculty.
     */
    public function approveLeaveRequest(LeaveRequest $leaveRequest, User $reviewer): void
    {
        $leaveRequest->update([
            'status' => 'Approved',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        $balance = LeaveBalance::getOrCreateBalance($leaveRequest->user_id);
        $balance->deductLeave($leaveRequest->leave_type, $leaveRequest->days_count);

        Notification::create([
            'user_id' => $leaveRequest->user_id,
            'message' => 'Your ' . $leaveRequest->leave_type . ' request has been APPROVED by ' . $reviewer->username,
        ]);
    }

    /**
     * Reject a leave request and notify faculty.
     */
    public function rejectLeaveRequest(LeaveRequest $leaveRequest, User $reviewer, string $reviewNotes): void
    {
        $leaveRequest->update([
            'status' => 'Rejected',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $reviewNotes,
        ]);

        Notification::create([
            'user_id' => $leaveRequest->user_id,
            'message' => 'Your ' . $leaveRequest->leave_type . ' request has been REJECTED by ' . $reviewer->username . '. Reason: ' . $reviewNotes,
        ]);
    }

    /**
     * Notify all coordinators and deans with a message.
     */
    private function notifySupervisors(string $message): void
    {
        $supervisors = User::whereIn('role_id', [1, 2])->get();
        foreach ($supervisors as $supervisor) {
            Notification::create([
                'user_id' => $supervisor->id,
                'message' => $message,
            ]);
        }
    }
}
