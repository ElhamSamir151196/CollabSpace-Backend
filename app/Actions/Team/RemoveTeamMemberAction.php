<?php

namespace App\Actions\Team;

use App\Interfaces\Repositories\TeamRepositoryInterface;
use App\Models\Team;
use App\Models\User;

class RemoveTeamMemberAction
{
    public function __construct(private readonly TeamRepositoryInterface $teams) {}

    public function handle(Team $team, User $member): void
    {
        $this->teams->removeMember($team, $member);

        activity('teams')->performedOn($team)->event('member_removed')
            ->withProperties(['user_id' => $member->id])
            ->log('Member removed');
    }
}
