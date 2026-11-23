<?php

namespace App\Actions\Project;

use App\Interfaces\Repositories\ProjectRepositoryInterface;
use App\Models\Project;
use App\Models\User;

class RemoveProjectGuestAction
{
    public function __construct(private readonly ProjectRepositoryInterface $projects) {}

    /**
     * Removes the direct access only. If the user is also in one of the
     * project's teams, they stay a project member through that team.
     */
    public function handle(Project $project, User $guest): void
    {
        $this->projects->detachGuest($project, $guest);

        activity('projects')->performedOn($project)->event('guest_removed')
            ->withProperties(['user_id' => $guest->id])
            ->log('Guest removed');
    }
}
