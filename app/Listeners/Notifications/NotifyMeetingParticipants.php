<?php

namespace App\Listeners\Notifications;

use App\Enums\AttendanceStatus;
use App\Enums\NotificationKind;
use App\Events\Meeting\MeetingCancelled;
use App\Events\Meeting\MeetingParticipantsInvited;
use App\Events\Meeting\MeetingRescheduled;
use App\Events\Meeting\MeetingScheduled;
use App\Models\Meeting;
use App\Models\User;
use App\Notifications\MeetingNotification;
use App\Services\Notifications\Recipients;
use Illuminate\Support\Facades\Notification;

/**
 * Invitations, reschedules and cancellations of meetings.
 */
class NotifyMeetingParticipants
{
    public function __construct(private readonly Recipients $recipients) {}

    public function handleMeetingScheduled(MeetingScheduled $event): void
    {
        $this->send($event->meeting, $event->invitedIds, NotificationKind::MeetingInvited, $event->by);
    }

    public function handleMeetingParticipantsInvited(MeetingParticipantsInvited $event): void
    {
        $this->send($event->meeting, $event->invitedIds, NotificationKind::MeetingInvited, $event->by);
    }

    public function handleMeetingRescheduled(MeetingRescheduled $event): void
    {
        $this->send($event->meeting, $this->attendees($event->meeting), NotificationKind::MeetingRescheduled, $event->by);
    }

    public function handleMeetingCancelled(MeetingCancelled $event): void
    {
        $this->send($event->meeting, $this->attendees($event->meeting), NotificationKind::MeetingCancelled, $event->by);
    }

    /**
     * Participants who did not decline.
     *
     * @return list<int>
     */
    private function attendees(Meeting $meeting): array
    {
        return $meeting->participants()
            ->wherePivot('attendance_status', '!=', AttendanceStatus::Declined->value)
            ->pluck('users.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @param  list<int>  $userIds
     */
    private function send(Meeting $meeting, array $userIds, NotificationKind $kind, User $by): void
    {
        Notification::send(
            $this->recipients->of($userIds, except: $by),
            new MeetingNotification($meeting, $kind, $by),
        );
    }
}
