<?php

namespace App\Actions\Meeting;

use App\Enums\AttendanceStatus;
use App\Exceptions\BusinessException;
use App\Interfaces\Repositories\MeetingRepositoryInterface;
use App\Models\Meeting;
use App\Models\User;

/**
 * A participant accepts or declines the invitation.
 */
class RespondToMeetingAction
{
    public function __construct(private readonly MeetingRepositoryInterface $meetings) {}

    public function handle(Meeting $meeting, User $user, AttendanceStatus $answer): void
    {
        if ($meeting->isCancelled()) {
            throw new BusinessException(__('This meeting was cancelled.'));
        }

        if ($meeting->end_at->isPast()) {
            throw new BusinessException(__('This meeting has already ended.'));
        }

        $this->meetings->updateParticipant($meeting, $user, [
            'attendance_status' => $answer->value,
            'responded_at' => now(),
        ]);
    }
}
