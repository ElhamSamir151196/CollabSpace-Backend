<?php

namespace App\Actions\Project;

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
        $this->projects->attachGuests($project, $userIds, $addedBy);

        activity('projects')->performedOn($project)->event('guests_added')
            ->withProperties(['user_ids' => $userIds])
            ->log('Guests added');
    }
}
