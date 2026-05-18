<?php

namespace App\Services;

use App\Models\ExamQuestionnaire;
use App\Models\Notification;
use App\Models\TeachingGuide;
use App\Models\User;

class NotificationService
{
    /**
     * Create a notification for a single user.
     */
    public function notify(int $userId, string $message): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'message' => $message,
        ]);
    }

    /**
     * Notify multiple users with the same message (bulk insert).
     */
    public function notifyMany(array $userIds, string $message): void
    {
        if (empty($userIds)) {
            return;
        }

        $now = now();
        $notifications = array_map(fn($userId) => [
            'user_id' => $userId,
            'message' => $message,
            'is_read' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ], $userIds);

        Notification::insert($notifications);
    }

    /**
     * Notify all supervisors (Dean + Coordinator).
     */
    public function notifySupervisors(string $message): void
    {
        $supervisorIds = User::whereIn('role_id', [1, 2])->pluck('id')->toArray();
        $this->notifyMany($supervisorIds, $message);
    }

    /**
     * Notify the faculty submitter that their exam questionnaire was approved.
     */
    public function notifyExamQuestionnaireApproved(ExamQuestionnaire $questionnaire, User $reviewer): void
    {
        $submitterId = (int) $questionnaire->submitted_by;
        if ($submitterId <= 0 || $submitterId === (int) $reviewer->id) {
            return;
        }

        $label = $questionnaire->title ?: $questionnaire->subject ?: 'your file';
        $type = strtoupper((string) ($questionnaire->submission_type ?: 'EQ'));
        $examPart = $questionnaire->exam_type ? " ({$questionnaire->exam_type})" : '';
        $reviewerLabel = $this->reviewerLabel($reviewer);

        $this->notify(
            $submitterId,
            "Your {$type} submission \"{$label}\"{$examPart} has been approved by the {$reviewerLabel}. Check Exam Questionnaires or Documents."
        );
    }

    /**
     * Notify the faculty submitter that their teaching guide was approved.
     */
    public function notifyTeachingGuideApproved(TeachingGuide $guide, User $reviewer): void
    {
        $submitterId = (int) $guide->user_id;
        if ($submitterId <= 0 || $submitterId === (int) $reviewer->id) {
            return;
        }

        $label = $guide->title ?: $guide->subject ?: 'your file';
        $reviewerLabel = $this->reviewerLabel($reviewer);

        $this->notify(
            $submitterId,
            "Your teaching guide \"{$label}\" has been approved by the {$reviewerLabel}. Check Teaching Guides or Documents."
        );
    }

    protected function reviewerLabel(User $reviewer): string
    {
        return $reviewer->isSecretary() ? 'Secretary' : 'Dean';
    }

    /**
     * Mark a notification as read (owned by user).
     */
    public function markAsRead(int $notificationId, int $userId): void
    {
        $notification = Notification::where('notification_id', $notificationId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $notification->update(['is_read' => true]);
    }
}
