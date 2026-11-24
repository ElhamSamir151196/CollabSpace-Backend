<?php

namespace App\Actions\Meeting;

use App\Enums\MeetingStatus;
use App\Events\Meeting\MeetingCancelled;
use App\Exceptions\BusinessException;
use App\Interfaces\Repositories\MeetingRepositoryInterface;
use App\Models\Meeting;
use App\Models\User;

/**
 * Cancelled meetings stay on the calendar (marked "cancelled") so people
 * can see what happened; deleting removes them.
 */
class CancelMeetingAction
{
    public function __construct(private readonly MeetingRepositoryInterface $meetings) {}

    public function handle(Meeting $meeting, User $by): Meeting
    {
        if ($meeting->isCancelled()) {
            throw new BusinessException(__('This meeting is already cancelled.'));
        }

        if ($meeting->end_at->isPast()) {
            throw new BusinessException(__('A meeting that has already ended cannot be cancelled.'));
        }

        $meeting = $this->meetings->update($meeting, [
            'status' => MeetingStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        MeetingCancelled::dispatch($meeting, $by);

        return $meeting;
    }
}
