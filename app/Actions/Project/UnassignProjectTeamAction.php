<?php

namespace App\Actions\Project;

use App\Interfaces\Repositories\ProjectRepositoryInterface;
use App\Models\Project;
use App\Models\Team;

class UnassignProjectTeamAction
{
    public function __construct(private readonly ProjectRepositoryInterface $projects) {}

    public function handle(Project $project, Team $team): void
    {
        $this->projects->detachTeam($project, $team);

        activity('projects')->performedOn($project)->event('team_unassigned')
            ->withProperties(['team_id' => $team->id])
            ->log('Team unassigned');
    }
}
