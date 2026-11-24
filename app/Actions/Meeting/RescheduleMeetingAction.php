<?php

namespace App\Actions\Meeting;

use App\DTOs\Meeting\MeetingSchedule;
use App\Events\Meeting\MeetingRescheduled;
use App\Exceptions\BusinessException;
use App\Interfaces\Repositories\MeetingRepositoryInterface;
use App\Models\Meeting;
use App\Models\User;

class RescheduleMeetingAction
{
    public function __construct(private readonly MeetingRepositoryInterface $meetings) {}

    public function handle(Meeting $meeting, MeetingSchedule $schedule, User $by): Meeting
    {
        if ($meeting->isCancelled()) {
            throw new BusinessException(__('A cancelled meeting cannot be rescheduled.'));
        }

        $previousStart = $meeting->start_at->toImmutable();

        $meeting = $this->meetings->update($meeting, [
            'start_at' => $schedule->startAt,
            'end_at' => $schedule->endAt,
        ]);

        MeetingRescheduled::dispatch($meeting, $previousStart, $by);

        return $meeting;
    }
}
