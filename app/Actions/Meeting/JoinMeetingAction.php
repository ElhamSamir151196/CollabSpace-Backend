<?php

namespace App\Actions\Meeting;

use App\Enums\AttendanceStatus;
use App\Exceptions\BusinessException;
use App\Interfaces\Repositories\MeetingRepositoryInterface;
use App\Models\Meeting;
use App\Models\User;

/**
 * "Join": records attendance and hands back the external meeting link.
 * Allowed from a few minutes before the start until the end.
 */
class JoinMeetingAction
{
    public const EARLY_JOIN_MINUTES = 15;

    public function __construct(private readonly MeetingRepositoryInterface $meetings) {}

    public function handle(Meeting $meeting, User $user): Meeting
    {
        if ($meeting->isCancelled()) {
            throw new BusinessException(__('This meeting was cancelled.'));
        }

        if (now()->lt($meeting->start_at->copy()->subMinutes(self::EARLY_JOIN_MINUTES))) {
            throw new BusinessException(__('The meeting has not started yet. You can join :minutes minutes before it starts.', [
                'minutes' => self::EARLY_JOIN_MINUTES,
            ]));
        }

        if (now()->gt($meeting->end_at)) {
            throw new BusinessException(__('This meeting has already ended.'));
        }

        if ($meeting->meeting_link === null) {
            throw new BusinessException(__('This meeting has no link yet.'));
        }

        // A manager who was not on the list joins as a participant.
        $this->meetings->addParticipants($meeting, [$user->id], AttendanceStatus::Accepted);

        $current = $meeting->participants()->whereKey($user->id)->first()?->attendance;

        $this->meetings->updateParticipant($meeting, $user, [
            'attendance_status' => AttendanceStatus::Attended->value,
            // Keep the first time they joined.
            'joined_at' => $current->joined_at ?? now(),
        ]);

        return $meeting;
    }
}
