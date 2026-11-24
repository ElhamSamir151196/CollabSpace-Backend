<?php

namespace App\Notifications;

use App\Enums\NotificationKind;
use App\Models\Meeting;
use App\Models\User;

/**
 * Invitation, reschedule, cancellation and reminder of a meeting:
 * same target and data, only the kind and the sentence change.
 */
class MeetingNotification extends AppNotification
{
    public function __construct(
        public readonly Meeting $meeting,
        public readonly NotificationKind $notificationKind,
        public readonly ?User $by = null,
    ) {
        parent::__construct();
    }

    public function kind(): NotificationKind
    {
        return $this->notificationKind;
    }

    protected function message(): string
    {
        $replace = [
            'name' => $this->by->name ?? '',
            'meeting' => $this->meeting->title,
            'when' => $this->meeting->start_at->format('M j \a\t H:i'),
        ];

        return match ($this->notificationKind) {
            NotificationKind::MeetingRescheduled => __(':name moved ":meeting" to :when.', $replace),
            NotificationKind::MeetingCancelled => __(':name cancelled ":meeting" (:when).', $replace),
            NotificationKind::MeetingReminder => __('":meeting" starts at :when.', $replace),
            default => __(':name invited you to ":meeting" on :when.', $replace),
        };
    }

    protected function target(): array
    {
        return [
            'type' => 'meeting',
            'id' => $this->meeting->id,
            'start_at' => $this->meeting->start_at->toIso8601String(),
        ];
    }

    protected function actor(): ?User
    {
        return $this->by;
    }
}
