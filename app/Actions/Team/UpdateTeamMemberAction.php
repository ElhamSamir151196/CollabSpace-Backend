<?php

namespace App\Actions\Team;

use App\DTOs\Team\UpdateTeamMemberData;
use App\Interfaces\Repositories\TeamRepositoryInterface;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateTeamMemberAction
{
    public function __construct(private readonly TeamRepositoryInterface $teams) {}

    public function handle(Team $team, User $member, UpdateTeamMemberData $data): void
    {
        if ($data->attributes === []) {
            return;
        }

        DB::transaction(function () use ($team, $member, $data): void {
            if ($data->makesLeader()) {
                $this->teams->clearLeaders($team, keepUserId: $member->id);
            }

            $this->teams->updateMember($team, $member, $data->attributes);
        });
    }
}
