<?php

namespace App\Actions\Team;

use App\DTOs\Team\CreateTeamData;
use App\Interfaces\Repositories\TeamRepositoryInterface;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateTeamAction
{
    public function __construct(
        private readonly TeamRepositoryInterface $teams,
        private readonly AddTeamMembersAction $addMembers,
    ) {}

    public function handle(CreateTeamData $data, User $creator): Team
    {
        return DB::transaction(function () use ($data, $creator): Team {
            $team = $this->teams->create([
                'name' => $data->name,
                'description' => $data->description,
                'color' => $data->color,
                'created_by' => $creator->id,
            ]);

            return $this->addMembers->handle($team, $data->members, $creator);
        });
    }
}
