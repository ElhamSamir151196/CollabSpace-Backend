<?php

namespace App\Actions\Project;

use App\Events\ProjectTeamsAssigned;
use App\Interfaces\Repositories\ProjectRepositoryInterface;
use App\Models\Project;
use App\Models\User;

class AssignProjectTeamsAction
{
    public function __construct(private readonly ProjectRepositoryInterface $projects) {}

    /**
     * Adds teams to the project. Teams that are already assigned are ignored.
     *
     * @param  list<int>  $teamIds
     */
    public function handle(Project $project, array $teamIds, ?User $by = null): void
    {
        $attached = $this->projects->attachTeams($project, $teamIds);

        if ($attached === []) {
            return;
        }

        activity('projects')->performedOn($project)->event('teams_assigned')
            ->withProperties(['team_ids' => $attached])
            ->log('Teams assigned');

        ProjectTeamsAssigned::dispatch($project, $attached, $by);
    }
}
