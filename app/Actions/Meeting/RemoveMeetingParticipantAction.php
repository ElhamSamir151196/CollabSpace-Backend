<?php

namespace App\Actions\Meeting;

use App\Exceptions\BusinessException;
use App\Interfaces\Repositories\MeetingRepositoryInterface;
use App\Models\Meeting;
use App\Models\User;

class RemoveMeetingParticipantAction
{
    public function __construct(private readonly MeetingRepositoryInterface $meetings) {}

    public function handle(Meeting $meeting, User $participant): void
    {
        if ($meeting->isCreatedBy($participant)) {
            throw new BusinessException(__('The organizer cannot be removed from the meeting.'));
        }

        $this->meetings->removeParticipant($meeting, $participant);
    }
}
