<?php

namespace App\Actions\Project;

use App\Exceptions\BusinessException;
use App\Interfaces\Repositories\ProjectRepositoryInterface;
use App\Interfaces\Repositories\TaskRepositoryInterface;
use App\Models\Project;
use App\Models\Team;

class UnassignProjectTeamAction
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projects,
        private readonly TaskRepositoryInterface $tasks,
    ) {}

    public function handle(Project $project, Team $team): void
    {
        if ($this->tasks->hasTasksForTeam($project, $team)) {
            throw new BusinessException(__('This team still has tasks in the project. Move or delete them first.'));
        }

        $this->projects->detachTeam($project, $team);

        activity('projects')->performedOn($project)->event('team_unassigned')
            ->withProperties(['team_id' => $team->id])
            ->log('Team unassigned');
    }
}
