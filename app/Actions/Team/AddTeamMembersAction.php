<?php

namespace App\Actions\Team;

use App\DTOs\Team\TeamMemberData;
use App\Interfaces\Repositories\TeamRepositoryInterface;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

/**
 * Adds members to a team. Members that are already in the team get
 * their position / leader flag updated instead of being duplicated.
 * A team has at most one leader: naming a new one removes the old one.
 */
class AddTeamMembersAction
{
    public function __construct(private readonly TeamRepositoryInterface $teams) {}

    /**
     * @param  list<TeamMemberData>  $members
     */
    public function handle(Team $team, array $members): Team
    {
        if ($members === []) {
            return $team;
        }

        DB::transaction(function () use ($team, $members): void {
            $newLeader = collect($members)->first(fn (TeamMemberData $member) => $member->isLeader);

            if ($newLeader !== null) {
                $this->teams->clearLeaders($team, keepUserId: $newLeader->userId);
            }

            $this->teams->upsertMembers($team, $members);
        });

        return $team;
    }
}
