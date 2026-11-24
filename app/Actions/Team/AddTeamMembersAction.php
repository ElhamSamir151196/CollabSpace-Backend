<?php

namespace App\Actions\Team;

use App\DTOs\Team\TeamMemberData;
use App\Events\TeamMembersAdded;
use App\Interfaces\Repositories\TeamRepositoryInterface;
use App\Models\Team;
use App\Models\User;
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
    public function handle(Team $team, array $members, ?User $by = null): Team
    {
        if ($members === []) {
            return $team;
        }

        $added = DB::transaction(function () use ($team, $members): array {
            $newLeader = collect($members)->first(fn (TeamMemberData $member) => $member->isLeader);

            if ($newLeader !== null) {
                $this->teams->clearLeaders($team, keepUserId: $newLeader->userId);
            }

            return $this->teams->upsertMembers($team, $members);
        });

        if ($added !== []) {
            TeamMembersAdded::dispatch($team, $added, $by);
        }

        return $team;
    }
}
