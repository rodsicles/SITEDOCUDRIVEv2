<?php

namespace App\Services;

use App\Models\Document;
use App\Models\ExamQuestionnaire;
use App\Models\Notification;
use App\Models\TeachingGuide;
use App\Models\User;

class NotificationService
{
    /**
     * Create a notification for a single user.
     */
    public function notify(int $userId, string $message, ?string $tone = null): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'message' => $message,
            'tone' => $tone,
        ]);
    }

    /**
     * Notify multiple users with the same message (bulk insert).
     */
    public function notifyMany(array $userIds, string $message, ?string $tone = null): void
    {
        if (empty($userIds)) {
            return;
        }

        $now = now();
        $notifications = array_map(fn ($userId) => [
            'user_id' => $userId,
            'message' => $message,
            'tone' => $tone,
            'is_read' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ], $userIds);

        Notification::insert($notifications);
    }

    /**
     * Active users with Dean or Secretary role (by role name — not hard-coded IDs).
     *
     * @return list<int>
     */
    public function deanAndSecretaryIds(?int $exceptUserId = null): array
    {
        $query = User::query()
            ->where('status', 'Active')
            ->whereHas('role', fn ($q) => $q->whereIn('role_name', ['Dean', 'Secretary']));

        if ($exceptUserId) {
            $query->where('id', '!=', $exceptUserId);
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Dean, Secretary, and Program Coordinators (for approval workflows).
     *
     * @return list<int>
     */
    public function supervisorIds(?int $exceptUserId = null): array
    {
        $query = User::query()
            ->where('status', 'Active')
            ->whereHas('role', fn ($q) => $q->whereIn('role_name', ['Dean', 'Secretary', 'Program Coordinator']));

        if ($exceptUserId) {
            $query->where('id', '!=', $exceptUserId);
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public function notifyDeanAndSecretary(string $message, ?string $tone = null, ?int $exceptUserId = null): void
    {
        $this->notifyMany($this->deanAndSecretaryIds($exceptUserId), $message, $tone);
    }

    /**
     * Notify all supervisors (Dean + Secretary + Program Coordinator).
     */
    public function notifySupervisors(string $message, ?string $tone = null, ?int $exceptUserId = null): void
    {
        $this->notifyMany($this->supervisorIds($exceptUserId), $message, $tone);
    }

    /**
     * Notify Dean/Secretary when someone uploads file(s). Skips self-uploads by Dean/Secretary.
     */
    public function notifyDeanOnFileUpload(
        User $uploader,
        int $fileCount,
        string $title,
        string $category,
        bool $pendingApproval = false,
    ): void {
        if ($uploader->isDeanOrSecretary() || $fileCount < 1) {
            return;
        }

        $uploader->loadMissing('employee');
        $name = $uploader->employee?->full_name ?? $uploader->username ?? 'A user';
        $countLabel = $fileCount === 1 ? '1 file' : "{$fileCount} files";
        $titleLabel = trim($title) !== '' ? "\"{$title}\"" : 'a file';
        $categoryLabel = trim($category) !== '' ? " ({$category})" : '';

        if ($pendingApproval) {
            $message = "{$name} submitted {$countLabel} for your approval: {$titleLabel}{$categoryLabel}. Review under Pending Teaching Guides or Exam Questionnaires.";
            $this->notifySupervisors($message, Notification::TONE_DANGER, $uploader->id);

            return;
        }

        $message = "{$name} uploaded {$countLabel}: {$titleLabel}{$categoryLabel}. Check Documents.";
        $this->notifyDeanAndSecretary($message, null, $uploader->id);
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
            "Your {$type} submission \"{$label}\"{$examPart} has been approved by the {$reviewerLabel}. Check Exam Questionnaires or Documents.",
            Notification::TONE_SUCCESS,
        );
    }

    /**
     * Notify the faculty submitter that their exam questionnaire was rejected.
     */
    public function notifyExamQuestionnaireRejected(ExamQuestionnaire $questionnaire, User $reviewer): void
    {
        $submitterId = (int) $questionnaire->submitted_by;
        if ($submitterId <= 0 || $submitterId === (int) $reviewer->id) {
            return;
        }

        $label = $questionnaire->title ?: $questionnaire->subject ?: 'your file';
        $type = strtoupper((string) ($questionnaire->submission_type ?: 'EQ'));
        $examPart = $questionnaire->exam_type ? " ({$questionnaire->exam_type})" : '';
        $reviewerLabel = $this->reviewerLabel($reviewer);
        $remarks = trim((string) ($questionnaire->remarks ?? ''));
        $reason = $remarks !== '' ? " Reason: {$remarks}" : '';

        $this->notify(
            $submitterId,
            "Your {$type} submission \"{$label}\"{$examPart} was rejected by the {$reviewerLabel}.{$reason}",
            Notification::TONE_DANGER,
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
            "Your teaching guide \"{$label}\" has been approved by the {$reviewerLabel}. Check Teaching Guides or Documents.",
            Notification::TONE_SUCCESS,
        );
    }

    /**
     * Notify the faculty submitter that their teaching guide was rejected.
     */
    public function notifyTeachingGuideRejected(TeachingGuide $guide, User $reviewer): void
    {
        $submitterId = (int) $guide->user_id;
        if ($submitterId <= 0 || $submitterId === (int) $reviewer->id) {
            return;
        }

        $label = $guide->title ?: $guide->subject ?: 'your file';
        $reviewerLabel = $this->reviewerLabel($reviewer);
        $remarks = trim((string) ($guide->remarks ?? ''));
        $reason = $remarks !== '' ? " Reason: {$remarks}" : '';

        $this->notify(
            $submitterId,
            "Your teaching guide \"{$label}\" was rejected by the {$reviewerLabel}.{$reason}",
            Notification::TONE_DANGER,
        );
    }

    protected function reviewerLabel(User $reviewer): string
    {
        return $reviewer->isSecretary() ? 'Secretary' : 'Dean';
    }

    /**
     * Notify the document uploader that the Dean permanently removed their file from the Recycle Bin.
     */
    public function notifyDocumentPermanentlyDeleted(Document $document, User $dean): void
    {
        $uploaderId = (int) $document->uploaded_by;
        if ($uploaderId <= 0 || $uploaderId === (int) $dean->id) {
            return;
        }

        $title = trim((string) ($document->document_title ?? ''));
        $label = $title !== '' ? "\"{$title}\"" : 'your file';

        $this->notify(
            $uploaderId,
            "The Dean permanently deleted {$label} from the Recycle Bin. It cannot be recovered.",
            Notification::TONE_DANGER,
        );
    }

    /**
     * Notify a faculty member or program coordinator that a task was assigned to them.
     */
    public function notifyTaskAssigned(int $assigneeUserId, string $taskTitle, ?User $assignedBy = null): Notification
    {
        $creator = $assignedBy?->employee?->full_name
            ?? $assignedBy?->name
            ?? $assignedBy?->username
            ?? 'the Dean';

        return $this->notify(
            $assigneeUserId,
            "New task assigned: \"{$taskTitle}\" from {$creator}. View it under My Tasks.",
            Notification::TONE_SUCCESS,
        );
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

    public function markAllAsRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }
}
