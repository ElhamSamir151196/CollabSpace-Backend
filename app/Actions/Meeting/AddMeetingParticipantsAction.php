<?php

namespace App\Actions\Meeting;

use App\Events\Meeting\MeetingParticipantsInvited;
use App\Interfaces\Repositories\MeetingRepositoryInterface;
use App\Models\Meeting;
use App\Models\User;

class AddMeetingParticipantsAction
{
    public function __construct(private readonly MeetingRepositoryInterface $meetings) {}

    /**
     * Invites project members (from any team). People already invited are ignored.
     *
     * @param  list<int>  $userIds
     */
    public function handle(Meeting $meeting, array $userIds, User $by): void
    {
        $added = $this->meetings->addParticipants($meeting, $userIds);

        if ($added !== []) {
            MeetingParticipantsInvited::dispatch($meeting, $added, $by);
        }
    }
}
