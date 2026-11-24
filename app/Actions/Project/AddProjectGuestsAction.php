<?php

namespace App\Actions\Project;

use App\Events\ProjectGuestsAdded;
use App\Interfaces\Repositories\ProjectRepositoryInterface;
use App\Models\Project;
use App\Models\User;

class AddProjectGuestsAction
{
    public function __construct(private readonly ProjectRepositoryInterface $projects) {}

    /**
     * Adds people to the project directly. People already added are ignored.
     *
     * @param  list<int>  $userIds
     */
    public function handle(Project $project, array $userIds, User $addedBy): void
    {
        $added = $this->projects->attachGuests($project, $userIds, $addedBy);

        if ($added === []) {
            return;
        }

        activity('projects')->performedOn($project)->event('guests_added')
            ->withProperties(['user_ids' => $added])
            ->log('Guests added');

        ProjectGuestsAdded::dispatch($project, $added, $addedBy);
    }
}
