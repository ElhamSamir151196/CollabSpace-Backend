<?php

namespace App\Actions\Team;

use App\DTOs\Team\UpdateTeamData;
use App\Interfaces\Repositories\TeamRepositoryInterface;
use App\Models\Team;

class UpdateTeamAction
{
    public function __construct(private readonly TeamRepositoryInterface $teams) {}

    public function handle(Team $team, UpdateTeamData $data): Team
    {
        if ($data->attributes === []) {
            return $team;
        }

        return $this->teams->update($team, $data->attributes);
    }
}
